<?php


include('includes/session.php');
include('includes/SQL_CommonFunctions.inc');

if (isset($_GET['LotKey']))  {
	$SelectedCOA=$_GET['LotKey'];
} elseif (isset($_POST['LotKey'])) {
	$SelectedCOA=$_POST['LotKey'];
}
if (isset($_GET['ProdSpec']))  {
	$SelectedSpec=$_GET['ProdSpec'];
} elseif (isset($_POST['ProdSpec'])) {
	$SelectedSpec=$_POST['ProdSpec'];
}

if (isset($_GET['QASampleID']))  {
	$QASampleID=$_GET['QASampleID'];
} elseif (isset($_POST['QASampleID'])) {
	$QASampleID=$_POST['QASampleID'];
}

//Get Out if we have no Certificate of Analysis
If ((!isset($SelectedCOA) || $SelectedCOA=='') AND (!isset($QASampleID) OR $QASampleID=='')){
        $Title = _('Select Certificate of Analysis To Print');
        include('includes/header.php');
		echo '<div class="block-header"><a href="" class="header-title-link"><h1>' . ' ' . $Title . '</h1></a></div>';
		echo '<div class="row gutter30">
<div class="col-xs-12">';
        echo '<form action="' . htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') .  '" method="post">
		
		<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />
		<div class="row">
<div class="col-xs-4">
<div class="form-group"> <label class="col-md-8 control-label">' . _('Enter Item') .'</label>
			<input type="text" class="form-control" name="ProdSpec" size="25" maxlength="25" /></div></div>
			
			<div class="col-xs-4">
<div class="form-group"> <label class="col-md-8 control-label">' . _('Enter Lot') .'</label>
			<input type="text" class="form-control" name="LotKey" size="25" maxlength="25" /></div>
		</div>
		<div class="col-xs-4">
<div class="form-group"> <br />
		<input type="submit" class="btn btn-info" name="PickSpec" value="' . _('Submit') . '" />
		</div>
		</div>
		</div>
		</form>
		</div>
		</div>
		<div class="row gutter30">
<div class="col-xs-12">
		<form action="' . htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') .  '" method="post">
	
		<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />
		<div class="row">
<div class="col-xs-4">
<div class="form-group"> <label class="col-md-8 control-label">' . _('Or Select Existing Lot') .'</label>';
	$SQLSpecSelect="SELECT sampleid,
							lotkey,
							prodspeckey,
							description
						FROM qasamples LEFT OUTER JOIN stockmaster
						ON stockmaster.stockid=qasamples.prodspeckey
						WHERE cert='1'
						ORDER BY lotkey";


	$ResultSelection=DB_query($SQLSpecSelect);
	echo '<select name="QASampleID" class="form-control">';
	echo '<option value="">' . str_pad(_('Lot/Serial'),15,'_'). str_pad(_('Item'),20, '_', STR_PAD_RIGHT). str_pad(_('Description'),20,'_') . '</option>';
	while ($MyRowSelection=DB_fetch_array($ResultSelection)){
		echo '<option value="' . $MyRowSelection['sampleid'] . '">' . str_pad($MyRowSelection['lotkey'],15, '_', STR_PAD_RIGHT). str_pad($MyRowSelection['prodspeckey'],20,'_') .htmlspecialchars($MyRowSelection['description'], ENT_QUOTES,'UTF-8', false)  . '</option>';
	}
	echo '</select></div>';
	echo '</div>
		<div class="col-xs-4">
<div class="form-group"><br />
		<input type="submit" class="btn btn-info" name="PickSpec" value="' . _('Submit') . '" />
		</div>
		</div>
		</div>
		</form>
		</div>
		</div>
		';
    include('includes/footer.php');
    exit();
}


$ErrMsg = _('There was a problem retrieving the Lot Information') . ' ' .$SelectedCOA . ' ' . _('from the database');
if (isset($SelectedCOA)) {
	$sql = "SELECT lotkey,
					description,
					name,
					method,
					qatests.units,
					type,
					testvalue,
					sampledate,
					prodspeckey,
					groupby
				FROM qasamples INNER JOIN sampleresults
				ON sampleresults.sampleid=qasamples.sampleid
				INNER JOIN qatests
				ON qatests.testid=sampleresults.testid
				LEFT OUTER JOIN stockmaster on stockmaster.stockid=qasamples.prodspeckey
				WHERE qasamples.lotkey='" .$SelectedCOA."'
				AND qasamples.prodspeckey='" .$SelectedSpec."'
				AND qasamples.cert='1'
				AND sampleresults.showoncert='1'
				ORDER by groupby, sampleresults.testid";
} else {
	$sql = "SELECT lotkey,
					description,
					name,
					method,
					qatests.units,
					type,
					testvalue,
					sampledate,
					prodspeckey,
					groupby
				FROM qasamples INNER JOIN sampleresults
				ON sampleresults.sampleid=qasamples.sampleid
				INNER JOIN qatests
				ON qatests.testid=sampleresults.testid
				LEFT OUTER JOIN stockmaster on stockmaster.stockid=qasamples.prodspeckey
				WHERE qasamples.sampleid='" .$QASampleID."'
				AND qasamples.cert='1'
				AND sampleresults.showoncert='1'
				ORDER by groupby, sampleresults.testid";
}
$result=DB_query($sql,$ErrMsg);

//If there are no rows, there's a problem.
if (DB_num_rows($result)==0){
	$Title = _('Print Certificate of Analysis Error');
	include('includes/header.php');
	
	 echo prnMsg( _('Unable to Locate Lot') . ' : ' . $SelectedCOA . ' ', 'error');
	echo '
			<div class="row">
			<p align="right"><a href="'. $RootPath . '/PDFCOA.php" class="btn btn-default">' . _('Certificate of Analysis') . '</a></p>
				
			</div>
			
			<br />';
	include('includes/footer.php');
	exit;
}
$PaperSize = 'Letter';
if ($QASampleID>'') {
	$myrow=DB_fetch_array($result);
	$SelectedCOA=$myrow['lotkey'];
	DB_data_seek($result,0);
}
include('includes/PDFStarter.php');
$pdf->addInfo('Title', _('Certificate of Analysis') );
$pdf->addInfo('Subject', _('Certificate of Analysis') . ' ' . $SelectedCOA);
$FontSize=12;
$PageNumber = 1;
$HeaderPrinted=0;
$line_height=$FontSize*1.25;
$RectHeight=12;
$SectionHeading=0;
$CurSection='NULL';
$SectionTitle='';
$SectionTrailer='';

$SectionsArray=array(array('PhysicalProperty',3, _('Physical Properties'), '', array(260,110,135),array(_('Physical Property'),_('Value'),_('Test Method')),array('left','center','center')),
					 array('',3, _('Header'), _('* Trailer'), array(260,110,135), array(_('Physical Property'),_('Value'),_('Test Method')),array('left','center','center')),
					 array('Processing',2, _('Injection Molding Processing Guidelines'), _('* Desicant type dryer required.'), array(240,265),array(_('Setting'),_('Value')),array('left','center')),
					 array('RegulatoryCompliance',2, _('Regulatory Compliance'), '', array(240,265),array(_('Regulatory Compliance'),_('Value')),array('left','center')));

while ($myrow=DB_fetch_array($result)){
	if ($myrow['description']=='') {
		$myrow['description']=$myrow['prodspeckey'];
	}
	$Spec=$myrow['description'];
	$SampleDate=ConvertSQLDate($myrow['sampledate']);

	foreach($SectionsArray as $row) {
		if ($myrow['groupby']==$row[0]) {
			$SectionColSizes=$row[4];
			$SectionColLabs=$row[5];
			$SectionAlign=$row[6];
		}
	}
	$TrailerPrinted=1;
	if ($HeaderPrinted==0) {
		include('includes/PDFCOAHeader.inc');
		$HeaderPrinted=1;
	}

	if ($CurSection!=$myrow['groupby']) {
		$SectionHeading=0;
		if ($CurSection!='NULL' AND $PrintTrailer==1) {
			$pdf->line($XPos+1, $YPos+$RectHeight,$XPos+506, $YPos+$RectHeight);
		}
		$PrevTrailer=$SectionTrailer;
		$CurSection=$myrow['groupby'];
		foreach($SectionsArray as $row) {
			if ($myrow['groupby']==$row[0]) {
				$SectionTitle=$row[2];
				$SectionTrailer=$row[3];
			}
		}
	}

	if ($SectionHeading==0) {
		$XPos=65;
		if ($PrevTrailer>'' AND $PrintTrailer==1) {
			$PrevFontSize=$FontSize;
			$FontSize=8;
			$line_height=$FontSize*1.25;
			$LeftOvers = $pdf->addTextWrap($XPos+5,$YPos,500,$FontSize,$PrevTrailer,'left');
			$FontSize=$PrevFontSize;
			$line_height=$FontSize*1.25;
			$YPos -= $line_height;
			$YPos -= $line_height;
		}
		if ($YPos < ($Bottom_Margin + 90)){ // Begins new page
			$PrintTrailer=0;
			$PageNumber++;
			include ('includes/PDFCOAHeader.inc');
		}
		$LeftOvers = $pdf->addTextWrap($XPos,$YPos,500,$FontSize,$SectionTitle,'center');
		$YPos -= $line_height;
		$pdf->setFont('','B');
		$pdf->SetFillColor(200,200,200);
		$x=0;
		foreach($SectionColLabs as $CurColLab) {
			$ColLabel=$CurColLab;
			$ColWidth=$SectionColSizes[$x];
			$x++;
			$LeftOvers = $pdf->addTextWrap($XPos+1,$YPos,$ColWidth,$FontSize,$ColLabel,'center',1,'fill');
			$XPos+=$ColWidth;
		}
		$SectionHeading=1;
		$YPos -= $line_height;
		$pdf->setFont('','');
	} //$SectionHeading==0
	$XPos=65;
	$Value='';
	if ($myrow['testvalue'] > '') {
		$Value=$myrow['testvalue'];
	} //elseif ($myrow['rangemin'] > '') {
	//	$Value=$myrow['rangemin'] . ' - ' . $myrow['rangemax'];
	//}
	if (strtoupper($Value) <> 'NB' AND strtoupper($Value) <> 'NO BREAK') {
		$Value.= ' ' . $myrow['units'];
	}
	$x=0;
	foreach($SectionColLabs as $CurColLab) {
		$ColLabel=$CurColLab;
		$ColWidth=$SectionColSizes[$x];
		$ColAlign=$SectionAlign[$x];
		switch ($x) {
			case 0;
				$DispValue=$myrow['name'];
				break;
			case 1;
				$DispValue=$Value;
				break;
			case 2;
				$DispValue=$myrow['method'];
				break;
		}
		$LeftOvers = $pdf->addTextWrap($XPos+1,$YPos,$ColWidth,$FontSize,$DispValue,$ColAlign,1);
		$XPos+=$ColWidth;
		$x++;
	}

	$YPos -= $line_height;
	$XPos=65;
	$PrintTrailer=1;
	if ($YPos < ($Bottom_Margin + 80)){ // Begins new page
		$pdf->line($XPos+1, $YPos+$RectHeight,$XPos+506, $YPos+$RectHeight);
		$PrintTrailer=0;
		$PageNumber++;
		include ('includes/PDFCOAHeader.inc');
	}
	//echo 'PrintTrailer'.$PrintTrailer.' '.$PrevTrailer.'<br>' ;
} //while loop

$pdf->line($XPos+1, $YPos+$RectHeight,$XPos+506, $YPos+$RectHeight);
if ($SectionTrailer>'') {
	$PrevFontSize=$FontSize;
	$FontSize=8;
	$line_height=$FontSize*1.25;
	$LeftOvers = $pdf->addTextWrap($XPos+5,$YPos,500,$FontSize,$SectionTrailer,'left');
	$FontSize=$PrevFontSize;
	$line_height=$FontSize*1.25;
	$YPos -= $line_height;
	$YPos -= $line_height;
}
if ($YPos < ($Bottom_Margin + 85)){ // Begins new page
	$PageNumber++;
	include ('includes/PDFCOAHeader.inc');
}

$FontSize=8;
$line_height=$FontSize*1.25;
$YPos -= $line_height;
$YPos -= $line_height;
$sql = "SELECT confvalue
			FROM config
			WHERE confname='QualityCOAText'";

$result=DB_query($sql, $ErrMsg);
$myrow=DB_fetch_array($result);
$Disclaimer=$myrow[0];
$LeftOvers = $pdf->addTextWrap($XPos+5,$YPos,500,$FontSize,$Disclaimer);
while (mb_strlen($LeftOvers) > 1) {
	$YPos -= $line_height;
	$LeftOvers = $pdf->addTextWrap($XPos+5,$YPos,500,$FontSize, $LeftOvers, 'left');
}

$pdf->OutputI('nERP' . 'COA' . date('Y-m-d') . '.pdf');
$pdf->__destruct();

?>