<?php

namespace IrideWeb\Core;

use DateTime;
use DateTimeZone;
use Exception;
use IrideWeb\Core\Cache\IWCache;

class Date
{
	private function __construct(){}

    /**
     * Ritorna il timestamp numerico formato UNIX partendo da una data in formato JAPPONESE
     * opzionale: la stringa $s potrebbe essere piu lunga e contenere anche un orario.. es. "2013/12/09 12:46"
     * @param $s
     * @return int
     */
    public static function timestamp($s)
	{
		if( strlen($s)<10 ) return 0;
        $s=str_replace("/","-",$s);
        try{
            $data=new \DateTime($s);
            return $data->getTimestamp();
        }
        catch(Exception $e){
            return "";
        }
	}

    /**
     * Ritorna l'ora corrente in formato HH.MM
     * @return bool|string
     */
	public static function now()
	{
		if( IWCache::have("date::now()") )
			return IWCache::get("date::now()");
		
		$s=date("Y/m/d");
		IWCache::set("date::now()",$s);
		return $s;
	}

	public static function getDataEOrarioNow(){
		$d = new DateTime("now", new DateTimeZone("Europe/Rome"));
		return $d->format("Y/m/d H.i");
	}

    /**
     * Ritorna l'ora corrente in formato HH.MM
     * @return bool|string
     */
	public static function OraNow()
	{
		$s=date("H.i");
		return $s;
	}

    /**
     * somma i minuti ad un orario. Se l'orario di partenza è 23.59, il risultato sarà sempre 23.59
     * in ogni caso, al massimo ritorna 23.59
     * @param $orario
     * @param int $i
     * @return string
     */
	public static function sommaOrari($orario,$i=0)
	{
		if($orario=="") return $orario;
		$orario=str_replace(":",".",$orario);
		if($orario=="23.59") return "23.59";
		
		list($hh,$mm)=explode(".",$orario);
		
		$ore=floor(($mm+$i)/60);
		$hh+=$ore;
		$mm+=$i-(60*$ore);
		
		$res=str_pad($hh,2,"0",STR_PAD_LEFT).".".str_pad($mm,2,"0",STR_PAD_LEFT);
		if( $res>"23.59" ) $res="23.59";
		
		return $res;
	}

    /**
     * Somma x giorni alla data passata (tutto in formato jap)
     * Se calcolo_fine_mese==true, calcolo i giorni passati dal fine mese corrente. In questo caso 30giorni,60giorni ecc si intendono "legali".
     * Esempio se oggi=2010/02/19, e $x=30, risulterà 2010/03/31
     * Se $usa_calendario_commerciale==true, e se $x è esattamente un multiplo di 30, richiama la funzione sommamesi()
     *
     * @param $x
     * @param string $oggi
     * @param bool $calcolo_fine_mese
     * @param bool $usa_calendario_commerciale
     * @return string
     */
	static function sommagiorni($x,$oggi="",$calcolo_fine_mese=false,$usa_calendario_commerciale=false)
	{
		if($oggi=="") $oggi=Date::now();
		if( $x==0 ) return $oggi;

		if( isIntero($x/30) && !$calcolo_fine_mese && $usa_calendario_commerciale) return Date::sommamesi($x/30,$oggi,false);
		
		//se ho scelto calcolo_fine_mese, aggiungo i giorni indicati e poi vado a fine mese
		if($calcolo_fine_mese)
		{
			//la data di partenza deve essere fissa al 15 del mese, cosi posso andare avanti e indietro tranquillo
			$oggi=substr($oggi,0,8)."15";
			
			$oggi=Date::sommagiorni(abs($x),$oggi);
			$oggi=substr($oggi,0,8).str_pad(Date::GetNGiorniNelMese($oggi),2,"0",STR_PAD_LEFT);
			return $oggi;
		}
        else {
            $oggi=str_replace("/","-",$oggi);
            try{
                $data=new \DateTime($oggi." 00:01");
                $data->modify($x." days");
                return $data->format("Y/m/d");
            }
            catch(Exception $e) {
                return "";
            }
        }
		
	}

    /**
     * Metodo che somma x mesi alla data passata come parametro.
     * Ritorna lo stesso giorno di x mesi dopo; se per caso il giorno � impossibile (es. 31/06) ritorna il primo giorno successivo disponibile (es. 01/07)   xxxx/xx/xx
     * @param $x
     * @param string $data
     * @param bool $se_data_non_valida_vai_giorno_successivo
     * @return string
     */
	static function sommamesi($x,$data="",$se_data_non_valida_vai_giorno_successivo=true)
	{
		if($data=="") $data=Date::now();
		$dd=substr($data,8,2);
		$mm=intval(substr($data,5,2));
		$yyyy=intval(substr($data,0,4));
		
		if( $x==0 ) return $data;
		if( $x>0 )
		{
			$mm+=$x%12;
			$yyyy+=floor($x/12);
			if( $mm>12 )
			{
				$yyyy+=floor($mm/12);
				$mm=$mm%12;
			}
			if( $mm<=0 ) $mm=12+$mm;
		}
		if( $x<0 )
		{
			$x=abs($x);
			$mm-=$x%12;
			$yyyy-=floor($x/12);
			if( $mm<=0 )
			{
				$yyyy--;
				$mm=12-abs($mm);
			}
		}

		$mm=str_pad($mm,2,"0",STR_PAD_LEFT);
		$ultimodelmese=Date::GetNGiorniNelMese("$yyyy/$mm/01");
		if( intval($dd>$ultimodelmese)  )
		{
			if($se_data_non_valida_vai_giorno_successivo) $tt=+1; else $tt=0;
			$ret=Date::sommagiorni($tt,"{$yyyy}/{$mm}/{$ultimodelmese}");
		}
		else
			$ret="$yyyy/$mm/$dd";
		
		return $ret;
	}

    /**
     * Traduce una data in formato japponese nell'equivalente dettata dal calendario corrente.
     * Il default è quello italiano, se il calendario è jap ritorno la stessa data passata
     * DateTime non supporta con timezone di default le date in formato DD/MM/YYYY per cui prima di istanziare l'oggetto trasformo la data in DD-MM-YYYY (uso -)
     * Controllo inoltre con un try catch se la stringa passata è valida, altrimenti ritorno vuoto
     * @param $s
     * @return string
     */
    public static function it($s)
	{
		if($s=="") return "";

        $s=str_replace("/","-",$s);
        try{
            $data=new \DateTime($s);
            $format="d/m/Y";
            $new_data=$data->format($format);
            if($GLOBALS["DATE_FORMAT"]=="mm/dd/yy") $new_data=$data->format("m/d/Y");
            if($GLOBALS["DATE_FORMAT"]=="yy/mm/dd") $new_data=$data->format("Y/m/d");
        }
        catch(Exception $e) {
            $new_data="";
        }


        return $new_data;
			
	}

    /**
     * traduce in formato GIAPPONESE una data nel formato ITALIANO/AMERICANO.
     * Se viene passata una data giapponese ritorna la data stessa.
     * DateTime non supporta con timezone di default le date in formato DD/MM/YYYY per cui prima di istanziare l'oggetto trasformo la data in DD-MM-YYYY (uso -)
     * Se sto usando il calendario AMERICANO, trasformo prima la data in italiano con gli slash poi la trasformo in ita con - e la consegno a DateTime
     * @param $s
     * @return string
     */
    public static function jap($s)
	{
		if($s=="" || strlen($s)!=10) return ""; //Per la validazione lato database aggiungo il controllo che non posso inserire più di 10 caratteri
		$s2=str_replace("-","/",$s);
		if($s2[4]=="/") return $s2;//Se la data è già in formato JAP ritorno la mia data

		//Altrimenti formatto la data considerando il calendario impostato
		$format="d-m-Y";
		if($GLOBALS["DATE_FORMAT"]=="mm/dd/yy") $format="m-d-Y";
		if($GLOBALS["DATE_FORMAT"]=="yy/mm/dd") $format="Y-m-d";

		$s2=str_replace("/","-",$s);
		try{
			$data=\DateTime::createFromFormat($format,$s2);
			if($data instanceof DateTime) $d = $data->format("Y/m/d");
			else die("data: ".$s2);
		}
		catch(Exception $e) {
			return "";
		}
		return $d;

	}

    /**
     * Traduce in formato giapponese il numero timestamp
     * @param $t
     * @return bool|string
     */
	static function japT($t)
	{
		return date("Y/m/d",$t);
	}

    /**
     * Ritorna il numero del mese partendo da una data japponese
     * @param string $s
     * @return int
     */
	static function getNMese($s="")
	{
		if($s=="") $s=Date::now();
		$t=Date::timestamp($s);
		$i=date("m",$t);
		return intval($i);
	}

    /**
     * Ritorna la descrizione del mese ("gennaio", "febbraio" ecc) partendo da una $s=data japponese
     * @param string $s
     * @return mixed
     */
    static function getLMese($s="")
	{
		$i=Date::getNMese($s);
		return Date::getMeseToNum($i);
	}

    /**
     * Ritorna il mese verbale dato il numero
     * @param int $i
     * @return mixed
     */
    static function getMeseToNum($i=1)
	{
		$mesi=array(t("Gennaio"),t("Febbraio"),t("Marzo"),t("Aprile"),t("Maggio"),t("Giugno"),t("Luglio"),t("Agosto"),t("Settembre"),t("Ottobre"),t("Novembre"),t("Dicembre"));
		return $mesi[$i-1];
	}

    /**
     * Ritorna la data JAP del primo del mese (a partire da una certa data)
     * @param string $oggi
     * @return string
     */
    static function getPrimoDelMese($oggi="")
	{
		if($oggi=="")$oggi=Date::now();
		$primo=substr($oggi,0,8)."01";
		return $primo;	
	}

    /**
     * Ritorna la data JAP dell'ultimo giorno del mese (a partire da una certa data)
     * @param string $oggi
     * @return string
     */
    static function getUltimoDelMese($oggi="")
	{
		if ($oggi=="") $oggi=Date::now();
		$mese=Date::getNMese($oggi);
        $anno=Date::getAnno($oggi);
        return $anno."/".iwPad($mese,2,"0")."/".cal_days_in_month(CAL_GREGORIAN,$mese,$anno);
	}

    /**
     * Ritorna il numero dell'anno (4 cifre) data una data japponese
     * @param string $s
     * @return bool|string
     */
    static function getAnno($s="")
	{
		if($s=="") $s=Date::now();
		$t=Date::timestamp($s);
		$i=date("Y",$t);
		return $i;
	}

    /**
     * Ritorna una data in formato giapponese dato giorno,mese, anno
     * @param $giorno
     * @param $mese
     * @param $anno
     * @return string
     * @deprecated
     */
    static function retData($giorno,$mese,$anno)
	{
		$giorno=str_repeat("0",2-strlen($giorno)).$giorno;
		$mese=str_repeat("0",2-strlen($mese)).$mese;
		$data="$anno/$mese/$giorno";
		return $data;
	
	}

    /**
     * Ritorna il numero di giorni presenti nel mese corrente
     * Il metodo accetta una data in formato japponese
     * @param $s
     * @return int
     */
    static function GetNGiorniNelMese($s)
	{
		$t=Date::timestamp($s);
		return intval(date("t",$t));
	}

    /**
     * Ritorna un'intervallo di date che indica il mese precedente quello attuale
     * @param string $data
     * @return array
     */
    static function getDateMesePrecedente($data="")
	{
		if($data=="") $data=Date::now();
		$m=intval(substr($data,5,2));
		$y=intval(substr($data,0,4));
		
		$m--;
		if($m==0){ $m=12; $y--; }
		
		$m=( strlen($m)==1 ? "0$m" : $m);
		
		$data_da=$y."/".$m."/01";
		$d=Date::GetNGiorniNelMese($data_da);
		$data_a=$y."/".$m."/".( strlen($d)==1 ? "0$d" : $d);
	
		return array($data_da,$data_a);
	}

    /**
     * Ritorna il numero del giorno della settimana (0=domenica, 1=lunedi ecc)
     * Richiede la data in formato JAP
     * @param $s
     * @return int
     */
    static function getNSettimana($s)
	{
		$t=Date::timestamp($s);
		$n=intval(date("w",$t));
		return $n;
	}

    /**
     * Ritorna la descrizione del giorno della settimana (lunedi, martedi ecc), se troncato = true restituisce le prime tre lettere del giorno
     * @param $s
     * @param bool $troncato
     * @return string
     */
    static function getGSettimana($s, $troncato=false)
	{
		$t=Date::timestamp($s);
		$i=intval(date("w",$t));
        if($troncato)
            return substr(Date::strSettimana($i), 0, 3);
        else
		    return Date::strSettimana($i);
	}

    /**
     * Ritorna la descrizione del giorno della settimana (lunedi, martedi ecc) da un UNIX timestamp
     * @param $s
     * @return string
     */
    static function getSettimanaFromTS($s)
	{
		$i=intval(gmdate("w",$s));
		return Date::strSettimana($i);
	}

    /**
     * Metodo che restituisce la descrizione del giorno della settimana
     * @param int $i
     * @param bool $troncato
     * @return string
     */
    static function strSettimana($i=0, $troncato=false)
	{
		$i=intval($i);
        $day="";
		if($i==0)
			$day=t("domenica");
		if($i==1)
            $day=t("lunedì");
		if($i==2)
            $day=t("martedì");
		if($i==3)
            $day=t("mercoledì");
		if($i==4)
            $day=t("giovedì");
		if($i==5)
            $day=t("venerdì");
		if($i==6)
            $day=t("sabato");
        if($troncato) $day=substr($day,0,3);
        return $day;
	}

    /**
     * Ritorna il giorno giuliano data una data e quindi sapendo l'anno (il 1 gennaio è il numero 1)
     * @param string $data
     * @return int
     */
    static function ggiuliano($data="")
	{
		if($data=="") $data=Date::now();
		$ts=Date::timestamp($data);
		$gg=intval(date("z",$ts))+1;
		return $gg;
	}

    /**
     * Funzione che calcola la differenza tra due date secondo il paramentro passato:
     * A - calcolo differenza in anni.
     * M - calcolo differenza in mesi.
     * S - calcolo differenza in settimane.
     * G - calcolo differenza in giorni.
     *
     * per mantenere la retrocompatibilità con la funzione precedente il paramentro di default è G
     * @param $partenza
     * @param $fine
     * @param string $tipo
     * @return float|int
     */
    static function getGiorniFraDate($partenza, $fine, $tipo="G")
    {
        switch ($tipo)
        {
            case "A" : $tipo = 365;
            break;
            case "M" : $tipo = (365 / 12);
            break;
            case "S" : $tipo = (365 / 52);
            break;
            case "G" : $tipo = 1;
            break;
        }
        $arr_partenza = explode("/", $partenza);
        $partenza_gg = $arr_partenza[2];
        $partenza_mm = $arr_partenza[1];
        $partenza_aa = $arr_partenza[0];
        $arr_fine = explode("/", $fine);
        $fine_gg = $arr_fine[2];
        $fine_mm = $arr_fine[1];
        $fine_aa = $arr_fine[0];
        $date_diff = mktime(12, 0, 0, $fine_mm, $fine_gg, $fine_aa) - mktime(12, 0, 0, $partenza_mm, $partenza_gg, $partenza_aa);
        $date_diff  = round(($date_diff / 60 / 60 / 24) / $tipo);
        return $date_diff;
    }

    /**
     * Funzione che fa la differenza di orari (del tipo HH:MM)
     * $start è l'ora di inizio, $end l'ora di fine, $sep è il carattere che divide le ore dai minuti, di default è ':'
     * Ritorna 2 valori:
     * 1) il numero di minuti di differenza fra i due orari (es 5, 50, 80)
     * 2) una stringa formattata in orario che indica la differenza (seguendo gli esempi sopra   00:05   00:50    01:20)
     * @param $start
     * @param $end
     * @param string $sep
     * @return array
     */
    static function getDiffOrari($start, $end,$sep=':')
	{
		$part = explode($sep, $start);
		$arr = explode($sep, $end);
		
		$diff = mktime($arr[0], $arr[1], 0,1,1,2004) - mktime($part[0], $part[1],0,1,1,2004);	//questi sono secondi
		$diff=$diff/60;	//ora � una differenza in minuti
		
		$HH=floor($diff / 60);		if( strlen($HH)==1 ) $HH="0".$HH;
		$MM=($diff % 60);			if( strlen($MM)==1 ) $MM="0".$MM;

		return array($diff,$HH.$sep.$MM);
	}

    /**
     * Funzione che fa la somma di orari (del tipo HH:MM)
     * $ora1 e $ora2 sono le ore da sommare, $sep è il carattere che divide le ore dai minuti, di default è ':'
     * Ritorna 2 valori:
     * 1) il numero di minuti di differenza fra i due orari (es 5, 50, 80)
     * 2) una stringa formattata in orario che indica la differenza (seguendo gli esempi sopra   00:05   00:50    01:20)
     * @param $ora1
     * @param $ora0
     * @param string $sep
     * @return array
     */
    static function getSumOrari($ora1,$ora0,$sep=':')
	{
		
		$ora0 = explode($sep,$ora0);
		$ora1 = explode($sep,$ora1);
		$ore     = $ora1[0] + $ora0[0]; 
		$minuti  = $ora1[1] + $ora0[1];
		$secondi = $ora1[2] + $ora0[2];
		
		if ($secondi > 59) { $minuti +=1;  }
		if ($minuti  > 59) { $minuti  = $minuti - 60;  $ore +=1;  }
		
		$ore     = str_pad($ore,2,0,STR_PAD_LEFT);
		$minuti  = str_pad($minuti,2,0,STR_PAD_LEFT);

		$sumDec=tofloat($ore*60) + tofloat($minuti);
		
		return array($sumDec,"$ore".$sep."$minuti");
	}

    /**
     * Funzione che restituisce un array contenente i giorni della settimana in formato jap.
     * A seconda del calendario, la posizione 0 è il lunedì o la domenica
     * @param string $CurrentDay
     * @param string $calendar
     * @return array
     */
    static function getArrayGiorniSettimana($CurrentDay="",$calendar="it")
	{
		if ($CurrentDay=="") $CurrentDay=Date::now();
		$weekDay=array();
		// Impostazione della settimana. Decremento il CurrentDay a seconda di che giorno della settimana �. 
		//A seconda del calendario, la posizione 0 � il luned� o la domenica
		$num_sett=Date::getNSettimana($CurrentDay);
		if ($num_sett==1)
		{
			$weekDay[0]=Date::sommagiorni(0,$CurrentDay);
			if($calendar=="us")
				$weekDay[0]=Date::sommagiorni(-1,$CurrentDay);
		}
		if ($num_sett==2)
		{
			$weekDay[0]=Date::sommagiorni(-1,$CurrentDay);
			if($calendar=="us")
				$weekDay[0]=Date::sommagiorni(-2,$CurrentDay);
		}
		else if ($num_sett==3)
		{
			$weekDay[0]=Date::sommagiorni(-2,$CurrentDay);
			if($calendar=="us")
				$weekDay[0]=Date::sommagiorni(-3,$CurrentDay);
		}
		else if ($num_sett==4)
		{
			$weekDay[0]=Date::sommagiorni(-3,$CurrentDay);
			if($calendar=="us")
				$weekDay[0]=Date::sommagiorni(-4,$CurrentDay);
		}
		else if ($num_sett==5)
		{
			$weekDay[0]=Date::sommagiorni(-4,$CurrentDay);
			if($calendar=="us")
				$weekDay[0]=Date::sommagiorni(-5,$CurrentDay);
		}
		else if ($num_sett==6)
		{
			$weekDay[0]=Date::sommagiorni(-5,$CurrentDay);
			if($calendar=="us")
				$weekDay[0]=Date::sommagiorni(-6,$CurrentDay);
		}
		else if ($num_sett==0)
		{
			$weekDay[0]=Date::sommagiorni(-6,$CurrentDay);
			if($calendar=="us")
				$weekDay[0]=Date::sommagiorni(0,$CurrentDay);	
		}
		//Dopo aver impostato il lunedi/domenica, imposto gli altri giorni
		$weekDay[1]=Date::sommagiorni(1,$weekDay[0]);
		$weekDay[2]=Date::sommagiorni(1,$weekDay[1]);
		$weekDay[3]=Date::sommagiorni(1,$weekDay[2]);
		$weekDay[4]=Date::sommagiorni(1,$weekDay[3]);
		$weekDay[5]=Date::sommagiorni(1,$weekDay[4]);
		$weekDay[6]=Date::sommagiorni(1,$weekDay[5]);
		
		return $weekDay;
	}

    
    /**
     * Metodo che controlla la validità di una data in formato jap
     * @param string $s
     * @return bool
     */
    static function isValidDate($s="")
	{
		if($s=="") return true;
		if(strlen($s)<10) return false;
		$anno=intval(substr($s,0,4));
		$mese=intval(substr($s,5,2));
		$giorno=intval(substr($s,8,2));
		
		if($giorno>=1 && $giorno<=31 && $mese>=1 && $mese<=12 && $anno>=1900 && $anno <= 2400 && $s{4}=="/" && $s{7}=="/")
			return true;
		else
			return false;
	}

    //Funzione che somma i giorni lavorativi ad una data (passare in formato jap)
    //$lavorativi può essere negativo
    static function sommagiorni_lavorativi($data,$lavorativi)
    {
        $anno = intval(substr($data,0,4));

        // Calcolo del giorno di Pasqua fino all’ultimo anno valido
        $array_pasquetta=array();
        for ($i=$anno-2; $i<=$anno+2; $i++)
        {
            $pasquetta = Date::sommagiorni(1,date("Y/m/d",easter_date($i)));
            $array_pasquetta[] = $pasquetta;
        }
        //var_dump($array_pasquetta);

        // questi giorni son sempre festivi a prescindere dall’anno
        //capodanno, epifania, 25 aprile, 1 maggio, 2 giugno, ferragosto, 1 novembre, natale, santo stefano
        $giorniFestivi = array("01/01","01/06","04/25","05/01","06/02","08/15","11/01","12/08","12/25","12/26");
        $i = 0;
        while (($lavorativi>0 && $i<$lavorativi) || ($lavorativi<0 && $i>$lavorativi))
        {
            if ($lavorativi>0) $data=Date::sommagiorni(1,$data); else $data=Date::sommagiorni(-1,$data);
            $giorno_data = Date::getGSettimana($data,true); //verifico il giorno: da 0 (dom) a 6 (sab)
            $mese_giorno = substr($data,5,5); // confronto con gg sempre festivi
             // Infine verifico che il giorno non sia sabato,domenica,festivo fisso o festivo variabile (pasquetta);
            if ($giorno_data !="sab" && $giorno_data != "dom" && !in_array($mese_giorno,$giorniFestivi) && !in_array($data,$array_pasquetta) )
             {
                 if ($lavorativi>0) $i++; else $i--;
             }
        }
        return $data;
    }

    /**
     * Funzione che converte la data da formato gregoriano JAP a ebraico
     * @param string $date data in formato JAP
     * @return string
     */
    static function gregorianoToEbraico($date)
    {
        list ($gregorianYear, $gregorianMonth, $gregorianDay) = explode ('/', $date);
        $jdDate = gregoriantojd ($gregorianMonth, $gregorianDay, $gregorianYear);
        //$gregorianMonthName = jdmonthname ( $jdDate, 1 );
        $hebrewDate = jdtojewish ($jdDate);
        list (, $hebrewDay, $hebrewYear) = explode ('/', $hebrewDate);
        $hebrewMonthName = jdmonthname ( $jdDate, 4);
        return "$hebrewMonthName $hebrewDay, $hebrewYear";
    }

    static function getNumTrimestre($date=""){
        if ($date=="") $date=Date::now();
        $mese=Date::getNMese($date);
        $res=$mese/3;
        if($res<=1) return 1;
        if($res<=2) return 2;
        if($res<=3) return 3;
        if($res<=4) return 4;
        return 0;
    }

    static function getArrayTrimestre($date=""){
        $numtrimestre=Date::getNumTrimestre($date);
        //costruisco arraytrimestri
        $array_trimestri=[
            ["01","02","03"],
            ["04","05","06"],
            ["07","08","09"],
            ["10","11","12"]
        ];
        return $array_trimestri[$numtrimestre-1];
    }

    static function getNomeTrimestre($date=""){
        $anno=Date::getAnno($date);
        $numtrimestre=Date::getNumTrimestre($date);
        $trimestre="T".$numtrimestre." ".$anno;
        return $trimestre;
    }

    static function getInizioFineTrimestre($date=""){
        $anno=Date::getAnno($date);
        $numtrimestre=Date::getNumTrimestre($date);
        $array_date=[
            [$anno."/01/01",$anno."/03/31"],
            [$anno."/04/01",$anno."/06/30"],
            [$anno."/07/01",$anno."/09/30"],
            [$anno."/10/01",$anno."/12/31"]
        ];
        return $array_date[$numtrimestre-1];
    }

}