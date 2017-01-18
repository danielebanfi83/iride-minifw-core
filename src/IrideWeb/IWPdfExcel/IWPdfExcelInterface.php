<?php
/**
 * Created by Iride Staff.
 * User: Daniele
 * Date: 15/02/16
 * Time: 14:58
 */

namespace IrideWeb\IWPdfExcel;


interface IWPdfExcelInterface
{
    public function Header();
    public function AddPage();
    public function MultiCell($w, $h, $txt, $border=0, $align='L', $height_ratio=1.25, $fill=false, $ln=1, $x='', $y='', $reseth=true, $stretch=0, $ishtml=false, $autopadding=true, $maxh=0, $valign='T', $fitcell=false);
    public function Cell($w, $h=0, $txt='', $border=0, $ln=0, $align='', $fill=false, $link='', $stretch=0, $ignore_min_height=false, $calign='T', $valign='M');
    public function SetFont($family, $style='', $size=null, $fontfile='', $subset='default', $out=true);
    public function SetWidths($widths=array());
    public function SetAligns($aligns=array());
    public function Row($data,$fill=0,$header=NULL,$border=1,$height=5,$height_ratio=1.25);
    public function RowTable($data);
    public function Footer();
    public function Output($filename="");
    public function Image($file, $x='', $y='', $w=0, $h=0, $type='', $link='', $align='', $resize=false, $dpi=300, $palign='', $ismask=false, $imgmask=false, $border=0, $fitbox=false, $hidden=false, $fitonpage=false, $alt=false, $altimgs=array());
    public function SetFillColor($c1,$c2,$c3);
    public function SetX($x, $rtloff=false);
    public function SetY($y, $resetx=true, $rtloff=false);
    public function SetXY($x, $y, $rtloff=false);
    public function GetX();
    public function GetY();
    public function SetTopMargin($margin);
    public function DrawBody();
    public function SetHeaderFillColor($r,$g=-1,$b=-1);
    public function SetHeaderLineWidth($val);
    public function DrawBodyTabella($nrows,$rows,$y0,$x0=10,$xf=200);
    public function drawFieldset($x0,$y0,$xf,$yf,$titolo,$bordotitolo=0);
    public function writeGS1Barcode($x, $y, $parametri, $w, $h, $style);
}