<?php

namespace backend\controllers;

use backend\helpers\LExcelHelper;
use common\models\Channel;
use common\models\mongo\statistics\UserMobileContactsMongo;

require_once '../../common/components/PHPExecl/PHPExcel.php';

class TestController extends BaseController
{

    public function actionContact(){
        $user_id = 12420;
//        $contact = UserMobileContactsMongo::find()->where(['user_id'=>$user_id])->all();
        $contact = UserMobileContactsMongo::find()->where(['user_id' => $user_id . '' ])->asArray()->all();
        var_dump($contact);
    }

    public function actionCsv2(){
        $data = [
            [
                'id' => 1,
                "name" => '兔子',
                'pass' => 123,
            ],
            [
                'id' => 2,
                'name' => '猴子',
                'pass' => 456,
            ],
            [
                'id' => 3,
                'name' => '狒狒',
                'pass' => 222,
            ],
        ];

        header("Content-Type: application/force-download");
        header("Content-type:text/csv;charset=UTF-8");
        header("Content-Disposition:filename=打开邮件导出".date("YmdHis").".csv");
//        echo chr(0xEF).chr(0xBB).chr(0xBF);
        echo "收件人邮箱,收件人姓名,发送时间\r";
        ob_end_flush();
        foreach($data as $rs) {
            echo $rs['id'].",".$rs['name'].",".$rs['pass']."\r"; flush();
//            flush();
        }
        exit;
    }

    public function actionCsv(){
        $data = [
            [
                'id' => 1,
                "name" => '兔子',
                'pass' => 123,
            ],
            [
                'id' => 2,
                'name' => '猴子',
                'pass' => 456,
            ],
            [
                'id' => 3,
                'name' => '狒狒',
                'pass' => 222,
            ],
        ];
        //输出Excel文件头
        header('Content-Type:application/vnd.ms-excel; charset=gb2312');
        header('Content-Disposition:attachment;filename="User.csv"');var_dump($data);exit;
        header('Cache-Control: max-age=0');
        //打开php文件句柄，表示直接输出到浏览器
        $fp = fopen('php://output', 'a');

        //计数器
        $n = 0;
        //每隔$limit行，刷新一下输出buffer,不要太大，也不要太小
        $limit = 100000;

        //逐行取出数据，不浪费内存
        $count = count($data);
        for ($i = 0; $i < $count; $i++){//刷新一下输出bbuffer,防止由于数据过多造成问题
            $n++;
            if ($limit == $n){
                ob_flush();
                flush();
                $n = 0;
            }
            $row = $data[$i];
            foreach ($row as $k => $v){
                $row[$i] = iconv('gbk', 'utf-8', $v);
            }
            fputcsv($fp, $row);
        }

        $this->_setcsvHeader('打款订单信息导出.csv');

        echo $this->_array2csv($data);
        exit;
    }

    public function actionExport(){
        $data = Channel::find()->asArray()->All(\yii::$app->db_kdkj_rd );
//        $data = [
//            [
//                'id' => 1,
//                "name" => '兔子',
//                'pass' => 123,
//            ],
//            [
//                'id' => 2,
//                'name' => '猴子',
//                'pass' => 456,
//            ],
//            [
//                'id' => 3,
//                'name' => '狒狒',
//                'pass' => 222,
//            ],
//        ];
        LExcelHelper::exportExcel($data, 'lige');
    }

    /**
     * @param array $data 导出数据
     * @param string $title excel标题名称
     * @throws \PHPExcel_Exception
     * @throws \PHPExcel_Reader_Exception
     * @throws \PHPExcel_Writer_Exception
     */
    public function actionTestExcel($data = [],$title = '标题'){
//        $data = [
//            [
//                'id' => 1,
//                "name" => 'LiGe',
//                'pass' => 123,
//            ],
//            [
//                'id' => 2,
//                'name' => 'GuoFan',
//                'pass' => 456,
//            ],
//            [
//                'id' => 3,
//                'name' => 'XiXi',
//                'pass' => 222,
//            ],
//        ];
        $objExcel = new \PHPExcel();
        $objWriter = new \PHPExcel_Writer_Excel5($objExcel);
        //设置属性
        $objExcel->getProperties()->setTitle("测试文档标题");

        $objExcel->setActiveSheetIndex(0);

        foreach ($data as $k => $v){
            $u1 = $k + 1;

            $u2 = 65;
            /*----------写入内容----------*/
            foreach ($v as $value){
                $objExcel->getActiveSheet()->setCellValue(strtolower(chr($u2++)).$u1, $value);
            }

        }

        $objExcel->setActiveSheetIndex(0);

//        $ex = '2003';
//        if ($ex == '2007'){
//            //导出excel2007文档
//            $outputFilrName = "test_excel.xls";
//            $objWriter->save($outputFilrName);
//            exit;
//        }else{
            //导出excel2003文档
            header('Content-Type:application/vnd.ms-excel');
            header('Content-Disposition:attachment;filename="'.$title.'.xls');
            header('Cache-Control:max-age=0');
            $objWriter = \PHPExcel_IOFactory::createWriter($objExcel, 'Excel5');
            $objWriter->save('php://output');
            exit;
//        }
    }

    public function actionExcel($data){
        $objExcel = new LExcelHelper();
        $objWriter = new \PHPExcel_Writer_Excel5($objExcel);
        //设置属性
//        $objExcel->getProperties()->setCreator('hello1');
//        $objExcel->getProperties()->setLastModifiedBy("hello2");
        $objExcel->getProperties()->setTitle("测试文档标题");
//        $objExcel->getProperties()->setSubject("测试文档主题");
//        $objExcel->getProperties()->setDescription("测试文档描述");
//        $objExcel->getProperties()->setKeywords("测试文档关键字");
//        $objExcel->getProperties()->setCategory("测试文档目录");

        $objExcel->setActiveSheetIndex(0);

//        $i = 0;
        //表头
//        $k1 = "aaa";
//        $k2 = "bbb";
//        $k3 = "ccc";
//        $k4 = "ddd";
//        $k5 = "eee";

//        $objExcel->getActiveSheet()->setCellValue('a1',"$k1");
//        $objExcel->getActiveSheet()->setCellValue('b1',"$k2");
//        $objExcel->getActiveSheet()->setCellValue('c1',"$k3");
//        $objExcel->getActiveSheet()->setCellValue('d1',"$k4");
//        $objExcel->getActiveSheet()->setCellValue('e1',"$k5");

//        $data = [1,2,3];
        foreach ($data as $k => $v){
            $u1 = $k + 1;

            /*----------写入内容----------*/
            $objExcel->getActiveSheet()->setCellValue('a'.$u1, $v['id']);
            $objExcel->getActiveSheet()->setCellValue('b'.$u1, $v['name']);
            $objExcel->getActiveSheet()->setCellValue('c'.$u1, $v['pass']);

        }

        //列的宽度
//        $objExcel->getActiveSheet()->getColumnDimension('A')->setWidth(10);
//        $objExcel->getActiveSheet()->getColumnDimension('A')->setWidth(10);
//        $objExcel->getActiveSheet()->getColumnDimension('A')->setWidth(15);
//        $objExcel->getActiveSheet()->getColumnDimension('A')->setWidth(15);
//        $objExcel->getActiveSheet()->getColumnDimension('A')->setWidth(15);

//        $objExcel->getActiveSheet()->getHeaderFooter()->setOddHeader('&L&BPersonal cash register&RPrinted on &D');
//        $objExcel->getActiveSheet()->getHeaderFooter()->setOddFooter('&L&B' . $objExcel->getProperties()->getTitle() . '&RPage &P of &N');
//
//        $objExcel->getActiveSheet()->getPageSetup()->setOrientation(\PHPExcel_Worksheet_PageSetup::ORIENTATION_PORTRAIT);
//        $objExcel->getActiveSheet()->getPageSetup()->setPaperSize(\PHPExcel_Worksheet_PageSetup::PAPERSIZE_A4);
        $objExcel->setActiveSheetIndex(0);
        $timestamp = time();
        $ex = '2007';
        if ($ex == '2007'){
            //导出excel2007文档
            $outputFilrName = $timestamp . "link_out.xls";
            $objWriter->save($outputFilrName);
            exit;
        }else{
            //导出excel2003文档
            header('Content-Type:application/vnd.ms-excel');
            header('Content-Disposition:attachment;filename="文件名称'.$timestamp.'.xls');
            header('Cache-Control:max-age=0');
            $objWriter = \PHPExcel_IOFactory::createWriter($objExcel, 'Excel5');
            $objWriter->save('php://output');
            exit;
        }
    }

    public function actionZhiMu(){
        for ($i = 65; $i < 91; $i ++){
            echo strtolower(chr($i)) . ' ';//小写
            echo strtoupper(chr($i)) . ' ';//大写
        }
    }
}