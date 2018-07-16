<?php 

class SLCSP
{

	public function main()
	{
		//Setting up arrays
		$plansArray = $this->convertCSVtoArray('plans.csv');
		$slcspArray = $this->convertCSVtoArray('slcsp.csv');
		$zipsArray = $this->convertCSVtoArray('zips.csv');

		$outputArray = array();
		//label row
		array_push($outputArray, $slcspArray[0]);

		$outputArray = $this->iterateThroughAllZipsOutput($slcspArray,
			$zipsArray,$plansArray,$outputArray);

		$fp = fopen('modifiedSLCSP.csv', 'w');

		foreach ($outputArray as $fields) {
		    fputcsv($fp, $fields);
		}

		fclose($fp);
		
	}

	private function iterateThroughAllZipsOutput($slcspArray,$zipsArray,$plansArray,$outputArray){
		for ($i=1; $i < count($slcspArray); $i++) { 
			$rateAreaAndStateArray = $this->findRateAreaForZip(
				$slcspArray[$i][0],$zipsArray);

			//if it's false don't look for it and skip to next one
			if ($rateAreaAndStateArray == false) {
				$rowArray = array(0 => $slcspArray[$i][0],'' );
				array_push($outputArray, $rowArray);
			}
			else{
					$matchingStateKeysArray = $this->matchingState(
					$rateAreaAndStateArray['state'],$plansArray);

					$matchingPlansKeysArray = $this->matchingStatesToSilverPlans(
						$matchingStateKeysArray,$plansArray);

					$matchingRateAreaKeysArray = $this->matchingRateAreaToSilverPlans(
						$matchingPlansKeysArray,$plansArray,$rateAreaAndStateArray['rate_area']);

					$finalRatesArray = $this->finalRatesArray($matchingRateAreaKeysArray,$plansArray);

					$result = array_unique($finalRatesArray);
					sort($result);
					//second lowest
					if (empty($result[1])) {
						if (!isset($result[0])) {
							$result[1]='';
						}
						else{
						$result[1]=$result[0];
						}
					}
					$rowArray = array(0 => $slcspArray[$i][0],1=>$result[1] );
					array_push($outputArray, $rowArray);
			}
		}
		return $outputArray;
	}

	private function finalRatesArray($matchingRateAreaKeysArray,$plansArray){

		$ratesArray = array();

		for ($i=1; $i < count($plansArray); $i++) { 
			if(in_array($i, $matchingRateAreaKeysArray)){
				array_push($ratesArray, $plansArray[$i][3]);
			}
		}
		return $ratesArray;
	}

	private function matchingRateAreaToSilverPlans($matchingPlansKeysArray,$plansArray,$rateArea){
		$matchingRateAreaKeysArray = array();

		$rateAreaKeys = array_column($plansArray, 4);

		for ($i=1; $i < count($rateAreaKeys); $i++) { 
			if ($rateAreaKeys[$i] == $rateArea) {
				if (in_array($i, $matchingPlansKeysArray)) {
					array_push($matchingRateAreaKeysArray, $i);
				}
			}
		}

		return $matchingRateAreaKeysArray;
	}

	private function matchingStatesToSilverPlans($matchingStateKeysArray,$plansArray){
		$matchingPlansKeysArray = array();

		$planTypes = array_column($plansArray, 2);

		for ($i=1; $i < count($planTypes); $i++) { 
			if ($planTypes[$i] == 'Silver') {
				if (in_array($i, $matchingStateKeysArray)) {
					array_push($matchingPlansKeysArray, $i);
				}
			}
		}
		
		return $matchingPlansKeysArray;
	}

	private function matchingState($state,$plansArray)
	{
		
		$matchingStateKeysArray = array();

		$states = array_column($plansArray, 1);

		for ($i=1; $i < count($states); $i++) { 
			if ($states[$i] == $state) {
				array_push($matchingStateKeysArray,$i);
			}
		}
		
		
		return $matchingStateKeysArray;
	}

	private function findRateAreaForZip($zipcode,$zipsArray)
	{	
		$rateAreaArray = array();

		$keysArray = array_keys(array_column($zipsArray, 0),$zipcode);

		foreach ($keysArray as &$value) {
    	array_push($rateAreaArray, $zipsArray[$value][4]);
		}

		if (empty($rateAreaArray)) {
			return false;
		}

		if (count(array_unique($rateAreaArray)) <= 1) {
			$rateArea = $zipsArray[$keysArray[0]][4];
			$state = $zipsArray[$keysArray[0]][1];
			$rateAreaAndStateArray = array('state' => $state, 'rate_area'=>$rateArea );
			return $rateAreaAndStateArray;
		}

		return false;
		
	}

	private function convertCSVtoArray($filename)
	{
		$array = array_map('str_getcsv', file($filename));
		return $array;
	}

}

$SLCSP = new SLCSP();
$SLCSP->main();
?>