<?php
/**
 * Created by Iride Staff.
 * User: Daniele
 * Date: 15/02/16
 * Time: 14:39
 */

namespace IrideWeb\IWPdfExcel;


use PHPExcel;
use PHPExcel_IOFactory;
use PHPExcel_Style_Border;
use PHPExcel_Writer_Excel2007;

class IWExcel extends PHPExcel implements IWPdfExcelInterface {

    private $row=1,$col=0;
    private $widths,$aligns;

    public function AddPage(){
        $this->createSheet();
    }

    public function Cell($w, $h=0, $txt='', $border=0, $ln=0, $align='', $fill=false, $link='', $stretch=0, $ignore_min_height=false, $calign='T', $valign='M'){
        $bordo=array("T"=>"top","L"=>"left","R"=>"right","B"=>"bottom");
        if($border!="" && $border!=0){
            $outline=array();
            for($b=0;$b<strlen($border);$b++) $outline[$bordo[$b]]=PHPExcel_Style_Border::BORDER_THICK;
            $styleArray=array("borders"=>array("outline"=>$outline));
            $this->getActiveSheet()->getStyleByColumnAndRow($this->col,$this->row)->applyFromArray($styleArray);
        }
        $this->getActiveSheet()->getRowDimension($this->row)->setRowHeight($h*3);
        $this->getActiveSheet()->getColumnDimensionByColumn($this->col)->setWidth($w);
        $this->getActiveSheet()->setCellValueExplicitByColumnAndRow($this->col,$this->row,$txt);
        if($ln==0) $this->col++;
        else {
            $this->row++;
            $this->col=0;
        }
    }

    public function MultiCell($w, $h, $txt, $border=0, $align='L', $height_ratio=1.25, $fill=false, $ln=1, $x='', $y='', $reseth=true, $stretch=0, $ishtml=false, $autopadding=true, $maxh=0, $valign='T', $fitcell=false){
        $this->Cell($w,$h,$txt,$border,1);
    }

    public function SetFont($family, $style='', $size=null, $fontfile='', $subset='default', $out=true){
        return "";
    }

    public function SetWidths($widths=array()){
        $this->widths=$widths;
    }

    public function SetAligns($aligns=array()){
        $this->aligns=$aligns;
    }

    public function Row($data,$fill=0,$header=NULL,$border=1,$height=5,$height_ratio=1.25){
        $n=count($data);
        $ln=0;
        for($i=0;$i<$n;$i++){
            if($i==$n-1) $ln=1;
            if($this->widths[$i]==0) $this->widths[$i]=20;
            $this->Cell($this->widths[$i],6,$data[$i],0,$ln);
        }
    }

    public function RowTable($data){
        $this->Row($data);
    }

    public function Header(){ }
    public function myHeader(){}
    public function Footer(){}


    public function SetImageBackground(){

    }

    public function Output($name="", $dest='I'){
        /**
         * @var $objWriter PHPExcel_Writer_Excel2007
         */
        $objWriter = PHPExcel_IOFactory::createWriter($this, "Excel2007");
        $name=str_replace(".pdf",".xlsx",$name);
        $objWriter->save($name);
        return $name;
    }

    public function Image($file, $x='', $y='', $w=0, $h=0, $type='', $link='', $align='', $resize=false, $dpi=300, $palign='', $ismask=false, $imgmask=false, $border=0, $fitbox=false, $hidden=false, $fitonpage=false, $alt=false, $altimgs=array()){

    }

    public function SetHeaderFillColor($r,$g=-1,$b=-1){}
    public function SetHeaderLineWidth($val){}

    public function SetFillColor($c1,$c2,$c3){
        $c1=str_pad(strtoupper(dechex($c1)),3,"0",STR_PAD_LEFT);
        $c2=str_pad(strtoupper(dechex($c2)),3,"0",STR_PAD_LEFT);
        $c3=str_pad(strtoupper(dechex($c3)),3,"0",STR_PAD_LEFT);
        $this->getActiveSheet()->getStyleByColumnAndRow($this->col,$this->row)->getFont()->getColor()->setARGB($c1.$c2.$c3);
    }

    public function SetX($x, $rtloff=false){}
    public function SetY($y, $resetx=true, $rtloff=false){}
    public function SetXY($x, $y, $rtloff=false){}

    public function GetX(){ return $this->row;}
    public function GetY(){ return $this->col;}
    public function SetTopMargin($margin=0){}

    public function DrawBody(){}
    public function DrawBodyTabella($nrows,$rows,$y0,$x0=10,$xf=200){}
    public function drawFieldset($x0,$y0,$xf,$yf,$titolo,$bordotitolo=0){}
    public function writeGS1Barcode($x, $y, $parametri, $w, $h, $style){}
}