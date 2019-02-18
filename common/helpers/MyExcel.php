<?php

namespace common\helpers;

use Yii;
use yii\base\Exception;

class MyExcel{
    /**
     * 获取PHPExcel Sheet对象
     * @param string $fullpath Excel文件完整存储路径
     * @return PHPExcel::Sheet Sheet对象
     */
    private function _get_excel_sheet($fullpath) {
        $type = \PHPExcel_IOFactory::identify($fullpath); //自动识别Excel版本
        $reader = \PHPExcel_IOFactory::createReader($type);
        $excel = $reader->load($fullpath);
        return $excel->getSheet(0);//读取第一个sheet
    }
 
    /**
     * 获取Excel数据
     * @param string $fullpath Excel文件完整存储路径
     * @param array $matchArr 映射关系
     * @param boolean $escapeTitle 是否跳过第一行的表头
     */
    private function _get_excel_data($fullpath, $matchArr, $escapeTitle ) {
        $sheet = $this->_get_excel_sheet($fullpath);
        $rows = $sheet->gethighestRow();// 取得总行数
        $result = [];
        $i = $escapeTitle === true ? 2 : 1;//若过滤第一行表头，内容从Excel的第二行开始读，否则从第一行开始读
        for (; $i <= $rows; $i++) {
            $arr = [];
            foreach ($matchArr as $key => $val) {
                $ci = \PHPExcel_Cell::columnIndexFromString($val) -1; //列索引减1(因为列的索引是从0开始计数)
                $arr[$key] = $sheet->getCellByColumnAndRow($ci, $i)->getValue();
            }
            $result[] = $arr;
        }
        return $result;
    }
 
    /**
     * @param string $fullpath 要读取的excel文档绝对路径
     * @param array $col 要读取的列名
     * @param boolean $escapeTitle 是否跳过第一行的表头, 默认跳过
     * */
    public function read($fullpath, $col, $escapeTitle = true){
 
        return $this->_get_excel_data($fullpath, $col, $escapeTitle);
    }

    public function export_baofu($data=array(), $form = array('title'=>'test' )) {
    // ini_set('memory_limit','128M');
    // include IA_ROOT."/framework/library/phpexcel/PHPExcel.php";
    // include IA_ROOT."/framework/library/phpexcel/PHPExcel/Writer/Excel2007.php";
    // $objPHPExcel = new PHPExcel();
    $objPHPExcel = new \PHPExcel();
    $objPHPExcel->setActiveSheetIndex(0);
    $objPHPExcel->getActiveSheet()->setTitle($form['title']."详情表");
    //合并单元格
    $objPHPExcel->getActiveSheet()->mergeCells('A1:D1');
    $objPHPExcel->getActiveSheet()->setCellValue('A1', $form['title']."详情表");
    $objPHPExcel->getActiveSheet()->setCellValue('A2', '商户订单号');
    $objPHPExcel->getActiveSheet()->setCellValue('B2', '借款人姓名');
    $objPHPExcel->getActiveSheet()->setCellValue('C2', '借款人手机');
    $objPHPExcel->getActiveSheet()->setCellValue('D2', '银行完成时间');
    $objPHPExcel->getActiveSheet()->setCellValue('E2', '上次派单机构');
    $objPHPExcel->getActiveSheet()->setCellValue('F2', '上次催收人');
    $objPHPExcel->getActiveSheet()->setCellValue('G2', '上次派单时间');
    $objPHPExcel->getActiveSheet()->setCellValue('H2', '本次派单机构');
    $objPHPExcel->getActiveSheet()->setCellValue('I2', '本次催收人');
    $objPHPExcel->getActiveSheet()->setCellValue('J2', '本次派单时间');
    $objPHPExcel->getActiveSheet()->setCellValue('K2', '订单ID');
    $objPHPExcel->getActiveSheet()->setCellValue('L2', '催单ID');
    $objPHPExcel->getActiveSheet()->setCellValue('M2', '催收状态');
    $objPHPExcel->getActiveSheet()->setCellValue('N2', '还款状态');
    $objPHPExcel->getActiveSheet()->setCellValue('O2', '应还本金');
    $objPHPExcel->getActiveSheet()->setCellValue('P2', '应还滞纳金');
    $objPHPExcel->getActiveSheet()->setCellValue('Q2', '本次流水金额');

    $count = count($data);
    for ($i = 2; $i <= $count+1; $i++) {
         $objPHPExcel->getActiveSheet()->setCellValue('A' . ($i+1), $data[$i-2][0]);
         $objPHPExcel->getActiveSheet()->setCellValue('B' . ($i+1), $data[$i-2]['username']);
         $objPHPExcel->getActiveSheet()->setCellValue('C' . ($i+1), $data[$i-2]['phone']);
         $objPHPExcel->getActiveSheet()->setCellValue('D' . ($i+1), $data[$i-2][2]);
         if(count($data[$i-2]['dispatch_records']) == 1){
            //只有本次派单
            $objPHPExcel->getActiveSheet()->setCellValue('E' . ($i+1), '');
            $objPHPExcel->getActiveSheet()->setCellValue('F' . ($i+1), '');
            $objPHPExcel->getActiveSheet()->setCellValue('G' . ($i+1), '');
            if(!isset($data[$i-2]['dispatch_records'][0])){
                $objPHPExcel->getActiveSheet()->setCellValue('H' . ($i+1), '');
                $objPHPExcel->getActiveSheet()->setCellValue('I' . ($i+1), '');
                $objPHPExcel->getActiveSheet()->setCellValue('J' . ($i+1), '');
            }else{
                $objPHPExcel->getActiveSheet()->setCellValue('H' . ($i+1), $data[$i-2]['dispatch_records'][0]['outside']);
                $objPHPExcel->getActiveSheet()->setCellValue('I' . ($i+1), $data[$i-2]['dispatch_records'][0]['loan_collection']);
                $objPHPExcel->getActiveSheet()->setCellValue('J' . ($i+1), date("Y-m-d H:i:s", $data[$i-2]['dispatch_records'][0]['created_at']));
            }
            
         }else{
            if(isset($data[$i-2]['dispatch_records'][2]) && date("Y-m-d", $data[$i-2]['dispatch_records'][1]['created_at']) == date("Y-m-d", $data[$i-2]['dispatch_records'][0]['created_at'])){
                //上次派单日期和本次一样，使用上上次派单记录
                $objPHPExcel->getActiveSheet()->setCellValue('E' . ($i+1),  $data[$i-2]['dispatch_records'][2]['outside']);
                $objPHPExcel->getActiveSheet()->setCellValue('F' . ($i+1), $data[$i-2]['dispatch_records'][2]['loan_collection']);
                $objPHPExcel->getActiveSheet()->setCellValue('G' . ($i+1), date("Y-m-d H:i:s", $data[$i-2]['dispatch_records'][2]['created_at']));

            }else{
                $objPHPExcel->getActiveSheet()->setCellValue('E' . ($i+1),  $data[$i-2]['dispatch_records'][1]['outside']);
                $objPHPExcel->getActiveSheet()->setCellValue('F' . ($i+1), $data[$i-2]['dispatch_records'][1]['loan_collection']);
                $objPHPExcel->getActiveSheet()->setCellValue('G' . ($i+1), date("Y-m-d H:i:s", $data[$i-2]['dispatch_records'][1]['created_at']));
            }
            $objPHPExcel->getActiveSheet()->setCellValue('H' . ($i+1), $data[$i-2]['dispatch_records'][0]['outside']);
            $objPHPExcel->getActiveSheet()->setCellValue('I' . ($i+1), $data[$i-2]['dispatch_records'][0]['loan_collection']);
            $objPHPExcel->getActiveSheet()->setCellValue('J' . ($i+1), date("Y-m-d H:i:s", $data[$i-2]['dispatch_records'][0]['created_at']));

         }
        
       
         $objPHPExcel->getActiveSheet()->setCellValue('K' . ($i+1), $data[$i-2]['order_id']);
         $objPHPExcel->getActiveSheet()->setCellValue('L' . ($i+1), $data[$i-2]['collectionId']);
         $objPHPExcel->getActiveSheet()->setCellValue('M' . ($i+1), $data[$i-2]['loan_status']);
         $objPHPExcel->getActiveSheet()->setCellValue('N' . ($i+1), $data[$i-2]['repay_status']);
         $objPHPExcel->getActiveSheet()->setCellValue('O' . ($i+1), $data[$i-2]['principal']);
         $objPHPExcel->getActiveSheet()->setCellValue('P' . ($i+1), $data[$i-2]['late_fee']);
         $objPHPExcel->getActiveSheet()->setCellValue('Q' . ($i+1), $data[$i-2][1]);

         // $objPHPExcel->getActiveSheet()->getStyle('A2:D'.($i+1))->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

         }
    //设置宽
    // $objPHPExcel->getActiveSheet()->getColumnDimension('A')->setWidth(10);
    // $objPHPExcel->getActiveSheet()->getColumnDimension('B')->setWidth(15);
    // $objPHPExcel->getActiveSheet()->getColumnDimension('C')->setWidth(15);
    // $objPHPExcel->getActiveSheet()->getColumnDimension('D')->setWidth(20);
    // $objPHPExcel->getActiveSheet()->getColumnDimension('E')->setWidth(15);
    // $objPHPExcel->getActiveSheet()->getColumnDimension('F')->setWidth(20);
    // $objPHPExcel->getActiveSheet()->getColumnDimension('K')->setWidth(20);
    // $objPHPExcel->getActiveSheet()->getColumnDimension('J')->setWidth(50);
    //字体对齐方式
 //    $objPHPExcel->getActiveSheet()->getStyle('A1:L1')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
    // $objPHPExcel->getActiveSheet()->getStyle('A1:L1')->getFont()->setSize(16);
    // $objPHPExcel->getActiveSheet()->getStyle('A2:L2')->getFont()->setSize(14);
    // $objPHPExcel->getActiveSheet()->getStyle('A1:L1')->getFont()->setBold(true);
    //创建Excel输入对象
    $write = new \PHPExcel_Writer_Excel5($objPHPExcel);
    header("Pragma: public");
    header("Expires: 0");
    header("Cache-Control:must-revalidate, post-check=0, pre-check=0");
    header("Content-Type:application/force-download");
    header("Content-Type:application/vnd.ms-execl");
    header("Content-Type:application/octet-stream");
    header("Content-Type:application/download");;
    header('Content-Disposition:attachment;filename="'.$form['title'].'详情表.xls"');
    header("Content-Transfer-Encoding:binary");
    $write->save('php://output');
}
}