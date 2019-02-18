<?php
namespace common\services;

use Yii;
use yii\base\Component;
use yii\validators\FileValidator;
use yii\web\UploadedFile;
use common\helpers\StringHelper;

// require_once Yii::getAlias('@common/api/oss') . '/sdk.class.php';

/**
 * 阿里oss模块封装
 * @author user
 */
class AliOssService extends Component
{
    public $bucket = 'test';

    public $ossService;

    public function init() {
        require_once Yii::getAlias('@common/api/oss') . '/sdk_wzd.class.php';
        $this->ossService = new \ALIOSS();
        $this->ossService->set_debug_mode(true);
        $this->bucket = DEFAULT_OSS_BUCKET;
    }

    /**
     * 附件列表
     * @param string $prefix
     * @param number $maxKeys
     */
    public function attachmentList($prefix='',$maxKeys=200)
    {
        $options = array(
                'delimiter' => '/',
                'prefix' => $prefix,
                'max-keys' => $maxKeys,
                //'marker' => 'myobject-1330850469.pdf',
        );
        $response = $this->ossService->list_object($this->bucket, $options);
        if ($response->isOK()) {
            $sxe = new \SimpleXMLElement($response->body);
            $contents = $sxe->Contents;
            $prefixes = $sxe->CommonPrefixes;
        } else {
            $contents = [];
            $prefixes = [];
        }
        return ['contents' => $contents,'prefixes' => $prefixes];
    }
    /**
     * 添加附件,支持上传文件数组--通过$_FILE方式上传
     * @param unknown $params
     * @param array $condition
     * @return number[]|string[]
     */
    public function attachmentAdd($params,$condition=[]){
        $type = isset($params['type']) ? $params['type'] : 'article';
        $name = isset($params['name']) ? $params['name'] : 'attach';
        $quality = isset($params['quality']) ? $params['quality'] : 0;
        $files = UploadedFile::getInstancesByName($name);
        $validator = new FileValidator();
        if($condition && isset($condition['extensions'])){
            $validator->extensions = $condition['extensions'];
        }else{
            $validator->extensions = ['jpg', 'png', 'gif'];
        }
        if($condition && isset($condition['maxSize'])){
            $validator->maxSize = $condition['maxSize'];
        }else{
            $validator->maxSize = 1024 * 1024;
        }
        $validator->checkExtensionByMimeType = false;
        $error = '';
        foreach($files as $file){
            if (!$validator->validate($file, $error)) {
                return ['code'=>-1,'msg'=>'文件不符合要求：' . $error];
            }
        }
        $file_urls = [];
        foreach($files as $file){
            if ($type == 'dailyshake') {
                $object = $type . '/' . StringHelper::generateUniqid() . '.' . $file->extension;
            } elseif($type == 'loan_record') {
                $object = $type . '/shop/' . StringHelper::generateUniqid() . '.' . $file->extension;
            }else {
                $object = $type . '/' . date('Ymd', time()) . '/' . StringHelper::generateUniqid() . '.' . $file->extension;
            }
            $file_path = $file->tempName;
            if($quality){//图片压缩比例1~100
                $image=Yii::$app->image->load($file_path);
                $response = $this->ossService->upload_file_by_content($this->bucket, $object, ['content'=>$image->render(NULL,$quality)]);
            }else{
                $response = $this->ossService->upload_file_by_file($this->bucket, $object, $file_path);
            }
            if (!$response->isOK()) {
                return ['code'=>-1,'msg'=>'上传失败'];
            }
            // $file_urls[] = 'http://res.koudailc.com/' . $object;
            $file_urls[] = OSS_RES_URL . $object;

        }
        return ['code'=>0,'msg'=>'上传成功','file_urls'=>$file_urls];
    }
    /**
     * 添加附件,支持上传文件数组--通过base64方式上传
     * @param unknown $params
     * @param array $condition
     * @return number[]|string[]
     */
    public function attachmentAddByBase64($params,$condition=[]){
        $type = isset($params['type']) ? $params['type'] : 'article';
        $name = isset($params['name']) ? $params['name'] : 'attach';
        $attachs = isset($_REQUEST[$name]) ? $_REQUEST[$name] : [];
        if(!$attachs) return ['code'=>-1,'msg'=>'上传文件为空'];
        if(!is_array($attachs)){
            $attachs = [$attachs];
        }
        $extensions = ['jpg', 'png', 'gif'];
        if($condition && isset($condition['extensions'])){
            $extensions = $condition['extensions'];
        }
        $files = [];
        foreach($attachs as $attach){
            $expData = explode(';',$attach);
            if(!isset($expData[0])){
                continue;
            }
            $postfix   = explode('/',$expData[0]);
            if(!isset($postfix[1])){
                continue;
            }
            if(!in_array($postfix[1], $extensions)){
                return ['code'=>-1,'msg'=>'文件格式不符合要求'];
            }
            $img = str_replace("{$expData[0]};base64,", '', $attach);
            $img = str_replace(' ', '+', $img);
            $data = base64_decode($img);
            if ($data) {
                $files[] = ['extension'=>$postfix[1],'content'=>$data];
            }
        }
        $file_urls = [];
        foreach($files as $file){
            if ($type == 'dailyshake') {
                $object = $type . '/' . StringHelper::generateUniqid() . '.' . $file['extension'];
            } elseif($type == 'loan_record') {
                $object = $type . '/shop/' . StringHelper::generateUniqid() . '.' . $file['extension'];
            }elseif($type == 'voucher') {
                $user_id= isset($condition['user_id']) ? $condition['user_id'] : "";
                $object = 'ygb/order_voucher/'.$user_id . "/" . StringHelper::generateUniqid() . '.' . $file['extension'];
            }else {
                $object = $type . '/' . date('Ymd', time()) . '/' . StringHelper::generateUniqid() . '.' . $file['extension'];
            }
            $response = $this->ossService->upload_file_by_content($this->bucket, $object, ['content'=>$file['content']]);
            if (!$response->isOK()) {
                return ['code'=>-1,'msg'=>'上传失败'];
            }
            $file_urls[] = OSS_RES_URL . $object;
        }
        return ['code'=>0,'msg'=>'上传成功','file_urls'=>$file_urls];
    }
}
