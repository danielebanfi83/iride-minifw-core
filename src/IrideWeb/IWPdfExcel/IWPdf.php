<?php
/**
 * Created by Iride Staff.
 * User: Daniele
 * Date: 04/01/16
 * Time: 15:18
 */

namespace IrideWeb\IWPdfExcel;

use TCPDF;

class IWPdf extends TCPDF implements IWPdfExcelInterface
{
    protected $widths;
    protected $aligns;

    protected $HeaderFillColor=NULL;
    protected $HeaderFont=NULL;
    protected $OldHeaderFont=NULL;
    protected $HeaderLineWidth=NULL;
    protected $OldHeaderLineWidth=NULL;

    protected $truncate_strings=false;

    protected $is_excel=false;

    /**
     * @var IWExcel foglio di lavoro excel se voglio generare un excel
     */
    protected $workbook =  null;

    public function __construct($orientation="P",$unit='mm', $format='A4',$h_f="0",$title="")
    {
        parent::__construct($orientation,$unit,$format);
        $this->setFontSubsetting(false);
    }

    public function Header(){}

    public function Footer(){
        if(!is_null($this->workbook))  {
            $this->workbook->Footer();
            return;
        }

        $this->setCellPaddings(1,'',1);
        $this->SetY(-15);
        $this->SetFont("Helvetica",'I',8);
        $this->Cell(0,10,'Pag. '.$this->getAliasNumPage().'/'.$this->getAliasNbPages(),0,0,'C');
    }

    /**
     * Set the array of column widths
     * @param $w
     */
    public function setWidths($w=[])
    {
        if(!is_null($this->workbook)) {
            $this->workbook->SetWidths($w);
            return;
        }
        $this->widths=$w;
    }

    /**
     * Set the array of column alignments
     * @param $a
     */
    public function setAligns($a=[])
    {
        $this->aligns=$a;
    }

    public function SetFont($family, $style='', $size=null, $fontfile='', $subset='default', $out=true){
        if(!is_null($this->workbook)) {
            $this->workbook->SetFont($family, $style, $size, $fontfile, $subset, $out);
            return;
        }

        if($family=="FreeSans") $family="Helvetica";
        parent::SetFont($family, $style, $size, $fontfile, $subset, $out);
    }

    public function MultiCell($w, $h, $txt, $border=0, $align='L', $height_ratio=1.25, $fill=false, $ln=1, $x='', $y='', $reseth=true, $stretch=0, $ishtml=false, $autopadding=true, $maxh=0, $valign='T', $fitcell=false) {
        if(!is_null($this->workbook)) {
            $this->workbook->MultiCell($w, $h, $txt, $border, $align, $height_ratio, $fill, $ln, $x, $y, $reseth, $stretch, $ishtml, $autopadding, $maxh, $valign, $fitcell);
            return;
        }
        $height_ratio_old=$this->getCellHeightRatio();
        $this->setCellHeightRatio($height_ratio);
        parent::MultiCell($w,$h,$txt,$border,$align,$fill,$ln,$x,$y,$reseth,$stretch,$ishtml,$autopadding,$maxh,$valign,$fitcell);
        $this->setCellHeightRatio($height_ratio_old);
    }

    public function Row($data,$fill=0,$header=NULL,$border=1,$height=5,$height_ratio=1.25)
    {
        if(!is_null($this->workbook)) {
            $this->workbook->Row($data,$fill=0,$header=NULL,$border=1,$height=5,$height_ratio=1.25);
            return;
        }

        if( !is_array($fill) ) //If $fill is not an array, I convert in array for all the cell written
            $fill=array_fill(0,100,$fill);
        //Calculate the height of the row
        $nb=0;
        for($i=0;$i<count($data);$i++)
        {
            $nb=max($nb,$this->NbLines($this->widths[$i],$data[$i]));
        }
        $h=$height*$nb;
        //Issue a page break first if needed
        $chgPage=$this->checkPageBreak($h);

        //add header
        if($chgPage && isset($header)) {
            //setHeaderFont
            if(isset($this->HeaderFillColor))
                if($this->page>0)
                    $this->_out($this->HeaderFillColor);
            //setHeaderFont
            if (isset($this->HeaderFont)){
                $this->OldHeaderFont=array($this->FontFamily,$this->FontStyle,$this->FontSizePt);
                $this->SetFont($this->HeaderFont[0],$this->HeaderFont[1],$this->HeaderFont[2]);
            }
            if (isset($this->HeaderLineWidth)){
                $this->OldHeaderLineWidth=$this->LineWidth;
                $this->SetLineWidth($this->HeaderLineWidth);
            }
            for($i=0;$i<count($header);$i++)
            {
                $w=$this->widths[$i];
                $a=isset($this->aligns[$i]) ? $this->aligns[$i] : 'L';
                //Save the current position
                $x=$this->GetX();
                $y=$this->GetY();
                //Draw the border
                $this->Rect($x,$y,$w,$h,(isset($this->HeaderFillColor) ? 'F' : ''));
                $this->Rect($x,$y,$w,$h);
                //Print the text
                $this->MultiCell($w,$height,$header[$i],0,$a,$height_ratio);
                //Put the position to the right of the cell
                $this->SetXY($x+$w,$y);
            }
            $this->Ln($h);
            if(isset($this->HeaderFillColor))
                if($this->page>0)
                    $this->_out($this->FillColor);
            if (isset($this->HeaderFont)){
                $this->SetFont($this->OldHeaderFont[0],$this->OldHeaderFont[1],$this->OldHeaderFont[2]);
            }
            if (isset($this->HeaderLineWidth)){
                $this->SetLineWidth($this->OldHeaderLineWidth);
            }
        }

        //Draw the cells of the row
        $ln=0;
        for($i=0;$i<count($data);$i++)
        {
            $w=$this->widths[$i];
            $a=isset($this->aligns[$i]) ? $this->aligns[$i] : 'L';
            if($i==count($data)-1) $ln=1;
            //Save the current position
            $x=$this->GetX();
            $y=$this->GetY();
            //Draw the border
            $this->SetLineStyle(array("width"=>0.2));
            if($border==1)
            {
                $this->Rect($x,$y,$w,$h,($fill[$i]==1 ? 'F' : ''));
                $this->Rect($x,$y,$w,$h);
            }
            if(is_array($border))
            {
                $this->Rect($x,$y,$w,$h,'',$border);
            }
            //Print the text
            if($this->truncate_strings){
                $data[$i]=$this->getTruncatedStringForWidth($data[$i],$w);
                $this->Cell($w,$height,$data[$i],$border,$ln,$a,($border==0 ? $fill[$i] : 0));
            }
            else $this->MultiCell($w,$height,$data[$i],0,$a,$height_ratio,($border==0 ? $fill[$i] : 0),$ln);
            //Put the position to the right of the cell
            $this->SetXY($x+$w,$y);
        }
        //Go to the next line
        $this->Ln($this->truncate_strings?$height : $h);

    }

    protected function NbLines($w,$txt)
    {
        $margin=28.35/$this->k;
        $cmargin = $margin/10;
        //Computes the number of lines a MultiCell of width w will take
        $cw=&$this->CurrentFont['cw'];
        if($w==0)
            $w=$this->w-$this->rMargin-$this->x;

        $fontsize=$this->FontSize;
        $wmax=($w-2*($cmargin))*1000/($fontsize);
        $s=str_replace("\r",'',$txt);
        $nb=strlen($s);
        if($nb>0 and $s[$nb-1]=="\n")
            $nb--;
        $sep=-1;
        $i=0;
        $j=0;
        $l=0;
        $nl=1;
        while($i<$nb)
        {
            $c=$s[$i];
            if($c=="\n")
            {
                $i++;
                $sep=-1;
                $j=$i;
                $l=0;
                $nl++;
                continue;
            }
            if($c==' ')
                $sep=$i;
            $l+=$cw[ord($c)];
            if($l>$wmax)
            {
                if($sep==-1)
                {
                    if($i==$j)
                        $i++;
                }
                else
                    $i=$sep+1;
                $sep=-1;
                $j=$i;
                $l=0;
                $nl++;
            }
            else
                $i++;
        }
        return $nl;
    }

    public function RowTable($data){
        if(!is_null($this->workbook)){
            $this->workbook->RowTable($data);
            return;
        }
        $this->Row($data,0,NULL,0,4,1.5);
    }

    public function SetHeaderFillColor($r,$g=-1,$b=-1){
        if(!is_null($this->workbook)){
            $this->workbook->SetHeaderFillColor($r,$g,$b);
            return;
        }
        //Set color for all filling operations
        if(($r==0 && $g==0 && $b==0) || $g==-1)
            $this->HeaderFillColor=sprintf('%.3f g',$r/255);
        else
            $this->HeaderFillColor=sprintf('%.3f %.3f %.3f rg',$r/255,$g/255,$b/255);
    }

    public function SetHeaderLineWidth($val){
        if(!is_null($this->workbook)) {
            $this->workbook->SetHeaderLineWidth($val);
            return;
        }
        $this->HeaderLineWidth=$val;
    }

    /**
     * This method draws the lines alternating white and grey and its outer edge
     */
    public function DrawBody()
    {
        if(!is_null($this->workbook)) {
            $this->workbook->DrawBody();
            return;
        }

        $posy=$this->GetY(); //get my actual position
        $bmargin=$this->bMargin; //bottom margin
        $hpage=$this->h; //height of the page
        $nrows=floor(($hpage-$posy-$bmargin)/4);
        $this->SetFillColor(240,240,240);
        for($i=0;$i<$nrows;$i++)
        {
            $this->Cell(0,4,"",0,1,'C',($i%2));
        }
        $this->SetXY($this->lMargin,$posy);
        //Drawing the grid
        $widths=$this->widths;
        //adding margin position (considering distnaces as if it proceeded on the page border)
        array_unshift($widths,$this->lMargin);
        $this->Rect($this->lMargin,$posy,$this->w-$this->rMargin-$this->lMargin,floor($hpage-$posy-$bmargin));

    }

    /**
     * This method is like DrawBody but it draws lines only for the number of rows passed at the first parameter
     * @param int $nrows
     * @param array $rows
     * @param int $y0 first ordinate position of the table, the final one is calculated at the end of the rows
     * @param int $x0 first abscissa position of the table, the default part from the left edge
     * @param int $xf final abscissa position of the table, the default part from the right edge in Portrait page orientation
     */
    public function DrawBodyTabella($nrows,$rows,$y0,$x0=10,$xf=200){
        if(!is_null($this->workbook)) {
            $this->workbook->DrawBodyTabella($nrows,$rows,$y0,$x0=10,$xf=200);
            return;
        }
        $posy=$this->GetY(); //posizione attuale del 'cursore'
        $this->SetFillColor(240,240,240);
        for($i=0;$i<$nrows;$i++)
        {
            $this->Cell($xf-$x0,4,"",0,1,'C',($i%2));
        }
        $yf1=$this->GetY();
        $this->SetY($posy);
        foreach($rows as $row){
            $this->Row($row,0,NULL,0,4,1.5);
        }
        $yf2=$this->GetY();
        $yf=max($yf1,$yf2);
        $this->Rect($x0,$y0,$xf-$x0,$yf-$y0);

    }

    public function drawFieldset($x0,$y0,$xf,$yf,$titolo,$bordotitolo=0)
    {
        if(!is_null($this->workbook)) {
            $this->drawFieldset($x0, $y0, $xf, $yf, $titolo, $bordotitolo);
            return;
        }
        //drawing the fieldset
        $this->Rect($x0,$y0,$xf-$x0,$yf-$y0+3);

        //draw title fieldset
        $lunghezza_titolo=$this->GetStringWidth($titolo);  //get the length of the title
        $this->SetXY($x0+5,$y0-1.5); //setting at the top right of the fieldset
        $this->Cell($lunghezza_titolo+2,3,$titolo,$bordotitolo,0,"C",true);  //printing title

        //final position (adding space for successive strings)
        $this->SetXY($x0,$yf+10);
    }

    /**
     * This method prints a barcode in EAN-128
     * @param $x
     * @param $y
     * @param $parametri
     * @param $w
     * @param $h
     * @param $style
     */
    public function writeGS1Barcode($x, $y, $parametri, $w, $h, $style) {
        if(!is_null($this->workbook)){
            $this->workbook->writeGS1Barcode($x, $y, $parametri, $w, $h, $style);
            return;
        }
        $barcode="";
        $label="";
        $codici_variabili = array('10','21','22','23','240','241','242','250','251','253','254','30',
            '37','390','391','392','393','400','401','403','420','421','423','7002','7004','703');
        $prev_variable = true; //At the beginning I always write F1 char for EAN-128
        foreach($parametri as $code=>$value) {
            //If the previous field is variable I have to insert the control character 'F1'
            $barcode.=($prev_variable?chr(241):"").$code.$value;
            $label.="(".$code.")".$value;
            if(in_array($code, $codici_variabili)) $prev_variable=true;
            else $prev_variable=false;
        }
        $this->write1DBarcode($barcode, 'C128', $x, $y, $w, $h, 0.4, array_merge($style, array('label'=>$label)), 'N');
    }

    public function Output($name="",$dest="I"){
        if($name=="") $name = __DIR__."/".time().".pdf";

        if(!is_null($this->workbook)) {
            $out = "";
            $name = $this->workbook->Output($name);
            $dest = "F";
        }
        else $out = parent::Output($name,$dest);

        if($dest == "F")
            return json_encode(["filename" => $name] );

        return $out;
    }

    /**
     * Truncate the string considering its length and the width defined with the second parameter
     * @param $string
     * @param $width
     * @return string
     */
    public function getTruncatedStringForWidth( $string, $width )
    {
        $myString=$string;
        for($c = strlen($string); $c > 0; $c--){
            $myString=substr($string,0,$c);
            $myStringWidth=$this->GetStringWidth($myString);
            if($myStringWidth < $width) break;
        }
        return $myString;
    }

    /**
     * @return boolean
     */
    public function isTruncateStrings()
    {
        return $this->truncate_strings;
    }

    /**
     * @param boolean $truncate_strings
     */
    public function setTruncateStrings($truncate_strings)
    {
        $this->truncate_strings = $truncate_strings;
    }

    /**
     * @return null
     */
    public function getHeaderFont()
    {
        return $this->HeaderFont;
    }

    /**
     * @param null $HeaderFont
     */
    public function setHeaderFont($HeaderFont)
    {
        $this->HeaderFont = $HeaderFont;
    }

    /**
     * @return null
     */
    public function getOldHeaderLineWidth()
    {
        return $this->OldHeaderLineWidth;
    }

    /**
     * @param null $OldHeaderLineWidth
     */
    public function setOldHeaderLineWidth($OldHeaderLineWidth)
    {
        $this->OldHeaderLineWidth = $OldHeaderLineWidth;
    }

    /**
     * @return null
     */
    public function getOldHeaderFont()
    {
        return $this->OldHeaderFont;
    }

    /**
     * @param null $OldHeaderFont
     */
    public function setOldHeaderFont($OldHeaderFont)
    {
        $this->OldHeaderFont = $OldHeaderFont;
    }

    /**
     * @return boolean
     */
    public function isIsExcel()
    {
        return $this->is_excel;
    }

    /**
     * @param boolean $is_excel
     */
    public function setIsExcel($is_excel = true)
    {
        $this->is_excel = $is_excel;
        $this->workbook = new IWExcel();
    }
}