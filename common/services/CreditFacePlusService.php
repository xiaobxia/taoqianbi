<?php
namespace common\services;

use Yii;
use yii\base\Component;
use yii\base\Exception;
use common\exceptions\UserExceptionExt;
use common\models\CreditFaceIdCard;
use common\models\CreditFacePlus;
use common\models\CreditFacePlusLog;
use common\models\CreditFacePlusApiLog;
use common\models\ErrorMessage;
use common\models\LoanPerson;
use common\models\UserProofMateria;
use common\base\LogChannel;
use common\api\RedisQueue;

/**
 * CreditFacePlusService
 * Face++服务
 * ---------------------
 * @author Verdient。
 */
class CreditFacePlusService extends Component
{
    /**
     * @var $apiKey
     * 接口Key
     * ------------
     * @author Verdient。
     */
    public $apiKey;

    /**
     * @var $apiSecret
     * 接口Secret
     * ---------------
     * @author Verdient。
     */
    public $apiSecret;

    /**
     * 此接口提供基于人脸比对的身份核实功能，支持有源比对（调用者提供姓名、身份证号、和待核实人脸图）和无源比对（直接比对待核实人脸图和参照人脸图）。
     * 待核实人脸图可以由FaceID MegLive SDK产品提供，也可以由detect接口获得，还可以直接提供未经过detect方法检测的人脸图片。
     *
     * @version 2.0.5
     * @param $loanPerson
     * @param int $status
     * @return bool
     * @throws \Exception
     */
    public function faceplusplus($loanPerson, $status = 0) {
        $user_id = $loanPerson->id;
        $user_proof_materia = UserProofMateria::find()
            ->where([
                'user_id' => $user_id,
                'type' => UserProofMateria::TYPE_FACE_RECOGNITION,
            ])
            ->orderBy('id desc')
            ->one();
        if (!$user_proof_materia) {
            throw new \Exception("获取人脸照片失败");
        }

        $image = $user_proof_materia->url;
        $image = \str_replace('https', 'http', $image);
        $content = \file_get_contents($image);
        $idcard_name = $loanPerson->name;
        $idcard_number = $loanPerson->id_number;
        $post_data = [
            'api_key' => $this->apiKey,
            'api_secret' => $this->apiSecret,
            'comparison_type' => 1,
            'face_image_type' => 'raw_image',
            'idcard_name' => $idcard_name,
            'idcard_number' => $idcard_number,
            'image";filename="image' => $content
        ];

        $response = $this->getData($post_data) ?? '';

        //保存到face++调用API接口日志表中，方便查看
        $credit_face_plus_api_log = new CreditFacePlusApiLog();
        $credit_face_plus_api_log->user_id = $loanPerson->id;
        $credit_face_plus_api_log->raw = json_encode($response, JSON_UNESCAPED_UNICODE);
        $credit_face_plus_api_log->created_at =time();
        $credit_face_plus_api_log->save();

        //清除redis缓存
        $key=CreditFacePlus::FACE_PLUE_REDIS.$user_id;
        RedisQueue::del(["key"=>$key]);

        //"confidence": 88.862 大于 "1e-6": 79.9
        if (isset($response['time_used'])
            && isset($response['id_exceptions']['id_photo_monochrome'])
            && isset($response['id_exceptions']['id_attacked'])
            && isset($response['result_faceid']['confidence'])
            && isset($response['result_faceid']['thresholds']['1e-3'])
            && isset($response['result_faceid']['thresholds']['1e-4'])
            && isset($response['result_faceid']['thresholds']['1e-5'])
            && isset($response['result_faceid']['thresholds']['1e-6'])
//            && isset($response['result_faceid']['confidence']) >60
        ) {

            $transaction = Yii::$app->db_kdkj->beginTransaction();
            try {
                $credit_face_plus_log = new CreditFacePlusLog();
                $credit_face_plus_log->user_id = $loanPerson->id;
                $credit_face_plus_log->data = json_encode($response, JSON_UNESCAPED_UNICODE);
                if (!$credit_face_plus_log->save()) {
                    throw new \Exception("{$user_id} credit_face_plus_log 保存失败");
                }
                $credit_face_plus = CreditFacePlus::find()->where(['user_id' => $loanPerson->id])->one();
                if (!$credit_face_plus) {
                    $credit_face_plus = new CreditFacePlus();
                    $credit_face_plus->user_id = $loanPerson->id;
                }
                $credit_face_plus->time_used = $response['time_used'];
                $credit_face_plus->id_photo_monochrome = $response['id_exceptions']['id_photo_monochrome'];
                $credit_face_plus->id_attacked = $response['id_exceptions']['id_attacked'];
                $credit_face_plus->confidence = $response['result_faceid']['confidence'];
                $credit_face_plus['1e-3'] = $response['result_faceid']['thresholds']['1e-3'];
                $credit_face_plus['1e-4'] = $response['result_faceid']['thresholds']['1e-4'];
                $credit_face_plus['1e-5'] = $response['result_faceid']['thresholds']['1e-5'];
                $credit_face_plus['1e-6'] = $response['result_faceid']['thresholds']['1e-6'];
                $credit_face_plus->log_id = $credit_face_plus_log->id;
                $credit_face_plus->status = $status;
                $credit_face_plus->raw = json_encode($response, JSON_UNESCAPED_UNICODE);
                if (!$credit_face_plus->save()) {
                    throw new \Exception("{$user_id} credit_face_plus 保存失败");
                }
                $transaction->commit();
                return true;
            }
            catch (\Exception $e) {
                ErrorMessage::getMessage($user_id, \sprintf('[%s]face++保存失败,%s.', $user_id, $e->getMessage()), ErrorMessage::SOURCE_CERT);
                $transaction->rollback();
                throw $e;
            }
        }
        else {
            \yii::error(\sprintf('[%s]face++服务异常,%s.', $user_id, json_encode($response)), LogChannel::CREDIT_FACEPP);
            ErrorMessage::getMessage($user_id, \sprintf('[%s]face++服务异常,%s.', $user_id, json_encode($response)), ErrorMessage::SOURCE_CERT);
            throw new \Exception('face++ 验证失败');
        }
    }

    /**
     * 请求数据
     * @param $post_data
     * @return mixed
     */
    public function getData($post_data)
    {
        $curl = \curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://api.megvii.com/faceid/v2/verify",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => $post_data,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_HTTPHEADER => array(
                "cache-control: no-cache",
            ),
        ));
        $response = \curl_exec($curl);
        \curl_close($curl);

        return \json_decode($response, true);
    }

    /**
     * 检测和识别中华人民共和国第二代身份证。
     * 图片链接和图片内容任意传其中一个
     *
     * @param string $image_url 图片链接
     * @param string $image_file 图片内容
     * @return array
     */
    public function idCardCheck($image_url = null, $image_file = null) {
        if (is_null($image_url)) {
            $image_url = '';
        }


        $api = 'https://api.faceid.com/faceid/v1/ocridcard';
        try {
            $ctx = stream_context_create([ 'http' => ['timeout' => 30] ]);
            $content = $image_url ? file_get_contents($image_url, 0, $ctx) : $image_file;
            $curl = curl_init();
            $post_data = [
                'api_key' => $this->apiKey,
                'api_secret' => $this->apiSecret,
                'image";filename="image' => $content,
                'legality' => 1
            ];
            curl_setopt_array($curl, array(
                CURLOPT_SSL_VERIFYPEER => FALSE,
                CURLOPT_SSL_VERIFYHOST => FALSE,
                CURLOPT_URL => $api,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "POST",
                CURLOPT_POSTFIELDS => $post_data,//array('image'=>"@$image", 'api_key'=>"-WcwHwtQAOrd2PWptXyTLtpv0Un819SZ",'api_secret'=>"wyvtwwB1jxdg7V5eFNn94EbO0VO4Uu-X"),
                CURLOPT_HTTPHEADER => array(
                    "cache-control: no-cache",
                ),
            ));
            $response = curl_exec($curl);
            $errno = curl_errno($curl);
            $error = curl_error($curl);
            curl_close($curl);

            if (!$response) {
                $ret = [
                    'code' => -1,
                    'data' => null,
                    'message' => '请求错误：' . $errno . '-' . $error
                ];
            } else {
                $response = json_decode($response, true);
                if (!$response) {
                    $ret = [
                        'code' => -1,
                        'data' => null,
                        'message' => '解析请求结果出错'
                    ];
                } else if (!empty($response['error'])) {
                    $ret = [
                        'code' => -1,
                        'data' => null,
                        'message' => $response['error'],
                    ];
                } else {
                    $ret = [
                        'code' => 0,
                        'data' => $response,
                        'message' => null
                    ];
                }
            }
        }
        catch (Exception $ex) {
            $ret = [
                'code' => -1,
                'data' => null,
                'message' => $ex->getMessage()
            ];
        }

        return $ret;
    }

    /**
     *
     * 此接口检测一张照片中的人脸，并且将检测出的人脸保存到FaceID平台里，便于后续的人脸比对。
     * 因为FaceID Verify接口假设待比对的照片必须是有且只有一张人脸的正面大头照，对于不使用FaceID MegLive活体检查模块获得大头照的用户，
     * Detect接口提供了从一张图片里直接获得大头照的方式。
     *
     * @param string $image_file 通过非MegLive的途径获得的真人人脸照片。
     * @param string $image_url 同上(两选一)
     * @return array
     * @version 1.0.1
     */
    public function faceDetect($image_url = null, $image_file = null)
    {
        if (is_null($image_url)) {
            $image_url = '';
        }

        $url = 'https://api.faceid.com/faceid/v1/detect';
        $ret = null;
        try {
            $content = $image_url ? file_get_contents($image_url) : $image_file;
            $curl = curl_init();
            $post_data = [
                'api_key' => $this->apiKey,
                'api_secret' => $this->apiSecret,
                'image";filename="image' => $content
            ];
            curl_setopt_array($curl, array(
                CURLOPT_SSL_VERIFYPEER => FALSE,
                CURLOPT_SSL_VERIFYHOST => FALSE,
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "POST",
                CURLOPT_POSTFIELDS => $post_data,
                CURLOPT_HTTPHEADER => array("cache-control: no-cache")
            ));
            $response = curl_exec($curl);
            $err_no = curl_errno($curl);

            if ($err_no != 0) {
                $ret = [
                    'code' => -1,
                    'data' => null,
                    'message' => '请求错误：' . $err_no . '-' . curl_error($curl),
                ];
            } else {
                $response = json_decode($response, true);
                if (!$response) {
                    $ret = [
                        'code' => -1,
                        'data' => null,
                        'message' => '解析请求结果出错'
                    ];
                } else if (!empty($response['error'])) {
                    $ret = [
                        'code' => -1,
                        'data' => null,
                        'message' => $response['error'],
                    ];
                } else {
                    $ret = [
                        'code' => 0,
                        'data' => $response,
                        'message' => null
                    ];
                }
            }

            curl_close($curl);
        } catch (Exception $e) {
            $ret = [
                'code' => -1,
                'data' => null,
                'message' => $e->getMessage()
            ];
        }
        return $ret;
    }

    /**
     * 获取身份证正面识别信息
     * @param $user_id
     * @param $name
     * @param $id_number
     * @return array|mixed
     */
    public function getCardFrontInfo($user_id, $name, $id_number)
    {
        if ($card_info = CreditFaceIdCard::find()
            ->where(['user_id' => $user_id, 'type' => CreditFaceIdCard::TYPE_FRONT])
            ->orderBy('id desc')
            ->one()) {
            return json_decode($card_info->data, true);
        }

        $user_proof_materia = UserProofMateria::findOneByType($user_id, UserProofMateria::TYPE_ID_CAR_Z);

        if (empty($user_proof_materia) || empty($user_proof_materia->url)) {
            return UserExceptionExt::throwCodeAndMsgExt('请先上传身份证正面照片');
        }

        $face_idcard_result = $this->idCardCheck($user_proof_materia->url);

        if ($face_idcard_result['code'] != 0
            || $face_idcard_result['data']['side'] != 'front'
            || empty($face_idcard_result['data']['id_card_number'])
        ) {
            return UserExceptionExt::throwCodeAndMsgExt('身份证正面识别失败，请重新上传清晰图片');
        }

        if (stripos($face_idcard_result['data']['id_card_number'], '*') !== false
            || mb_stripos($face_idcard_result['data']['id_card_number'], '*') !== false
        ) {
            return UserExceptionExt::throwCodeAndMsgExt('身份证正面识别失败，请重新上传清晰图片');
        }

        if (strtoupper($face_idcard_result['data']['id_card_number']) != strtoupper($id_number) || $face_idcard_result['data']['name'] != $name) {
            return UserExceptionExt::throwCodeAndMsgExt('身份证正面识别失败：信息不一致');
        }


        //身份证合法性检验结果
        if (isset($face_idcard_result['data']['legality']['ID Photo'])
            && isset($face_idcard_result['data']['legality']['Photocopy'])
            && isset($face_idcard_result['data']['legality']['Edited'])
            && $face_idcard_result['data']['legality']['ID Photo'] < 0.75
            && $face_idcard_result['data']['legality']['Photocopy'] < 0.9
            && $face_idcard_result['data']['legality']['Edited'] < 0.9) {

            return UserExceptionExt::throwCodeAndMsgExt('身份证正面识别失败，请重新上传清晰图片');
        }

        $face_id_card_front = new CreditFaceIdCard();
        $face_id_card_front->user_id = $user_id;
        $face_id_card_front->type = CreditFaceIdCard::TYPE_FRONT;
        $face_id_card_front->data = json_encode($face_idcard_result, JSON_UNESCAPED_SLASHES + JSON_UNESCAPED_UNICODE);

        if (!$face_id_card_front->save()) {
            return UserExceptionExt::throwCodeAndMsgExt('身份证识别失败，请重试');
        }

        return $face_idcard_result;
    }

    /**
     * 获取身份证反面识别信息
     * @param $user_id
     * @return array|mixed
     */
    public function getCardBackInfo($user_id)
    {
        if ($card_info = CreditFaceIdCard::find()
            ->where(['user_id' => $user_id, 'type' => CreditFaceIdCard::TYPE_BACK])
            ->orderBy('id desc')
            ->one()) {
            return json_decode($card_info->data, true);
        }

        $user_proof_materia = UserProofMateria::findOneByType($user_id, UserProofMateria::TYPE_ID_CAR_F);
        if (empty($user_proof_materia) || empty($user_proof_materia->url)) {
            return UserExceptionExt::throwCodeAndMsgExt('请先上传身份证反面照片');
        }


        $face_idcard_result = $this->idCardCheck($user_proof_materia->url);

        if ($face_idcard_result['code'] != 0
            || $face_idcard_result['data']['side'] != 'back'
        ) {
            return UserExceptionExt::throwCodeAndMsgExt('身份证反面识别失败，请重新上传清晰图片');
        }

        //身份证合法性检验结果
        if (isset($face_idcard_result['data']['legality']['ID Photo'])
            && isset($face_idcard_result['data']['legality']['Photocopy'])
            && isset($face_idcard_result['data']['legality']['Edited'])
            && $face_idcard_result['data']['legality']['ID Photo'] < 0.75) {

            return UserExceptionExt::throwCodeAndMsgExt('身份证反面识别失败，请重新上传清晰图片');
        }

        $face_id_card_front = new CreditFaceIdCard();
        $face_id_card_front->user_id = $user_id;
        $face_id_card_front->type = CreditFaceIdCard::TYPE_BACK;
        $face_id_card_front->data = json_encode($face_idcard_result, JSON_UNESCAPED_SLASHES + JSON_UNESCAPED_UNICODE);

        if (!$face_id_card_front->save()) {
            return UserExceptionExt::throwCodeAndMsgExt('身份证识别失败，请重试');
        }

        return $face_idcard_result;
    }


    /**
     * 获取用户身份证识别信息
     * @param LoanPerson $loanPerson
     * @return array
     */
    public function getIdCardInfo(LoanPerson $loanPerson) {
        $user_id = $loanPerson->id;
        $user_proof_materia_front = UserProofMateria::findOneByType($user_id, UserProofMateria::TYPE_ID_CAR_Z);
        if (!$user_proof_materia_front) {
            return [
                'code' => -1,
                'message' => '身份证正面图片不存在',
            ];
        }

        try {
            $result = $this->idCardCheck($user_proof_materia_front->url);

            if ($result['code'] != UserProofMateria::STATUS_NORMAL
                || $result['data']['side'] != 'front'
                || empty($result['data']['id_card_number'])) {
                return [
                    'code' => -1,
                    'message' => '身份证识别失败，请重新上传清晰图片',
                ];
            }

            if (stripos($result['data']['id_card_number'], '*') !== false || mb_stripos($result['data']['id_card_number'], '*') !== false) {
                return [
                    'code' => -1,
                    'message' => '身份证识别失败，请重新上传清晰图片',
                ];
            }

            $id_number = $loanPerson->id_number;
            $name = $loanPerson->name;
            if (strtoupper($result['data']['id_card_number']) != strtoupper($id_number) || $result['data']['name'] != $name) {
                return [
                    'code' => -1,
                    'message' => '身份证识别失败：信息不一致',
                ];
            }

            $face_id_card = new CreditFaceIdCard();
            $face_id_card->user_id = $user_id;
            $face_id_card->data = json_encode($result, JSON_UNESCAPED_SLASHES + JSON_UNESCAPED_UNICODE);
            if (!$face_id_card->save()) {
                return [
                    'code' => -1,
                    'message' => '身份证识别信息保存失败',
                ];
            }

            return [
                'code' => 0,
                'message' => 'success',
                'data' => $result['data']
            ];

        } catch (\Exception $e) {
            Yii::error(\sprintf('%s 身份证识别失败: %s', $user_id, $e->getMessage()), LogChannel::USER_ID_CARD);
            return [
                'code' => -1,
                'message' => $e->getMessage(),
            ];
        }
    }
}