<?php

namespace backend\helpers;

require_once '../../common/components/PHPExecl/PHPExcel.php';

class LExcelHelper extends \PHPExcel
{

    /**
     * 导出Excel表格
     * @param array $data 导出数据
     * @param string $title 表格名称
     * @throws \PHPExcel_Exception
     * @throws \PHPExcel_Reader_Exception
     * @throws \PHPExcel_Writer_Exception
     */
    public static function exportExcel($data = [],$title = '标题'){
        $objExcel = new \PHPExcel();
        $objWriter = new \PHPExcel_Writer_Excel5($objExcel);//2007Excel
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

}