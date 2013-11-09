<?php

require_once "DataObject.class.php";

class LogEntry extends DataObject {

  protected $data = array(
    "memberId" => "",
    "pageUrl" => "",
    "numVisits" => "",
    "lastAccess" => ""
  );

  public static function getLogEntries( $memberId ) {
    $conn = parent::connect();
    $sql = "SELECT * FROM " . TBL_ACCESS_LOG . " WHERE memberId = :memberId ORDER BY lastAccess DESC";

    try {
      $st = $conn->prepare( $sql );
      $st->bindValue( ":memberId", $memberId, PDO::PARAM_INT );
      $st->execute();
      $logEntries = array();
      foreach ( $st->fetchAll() as $row ) {
        $logEntries[] = new LogEntry( $row );
      }
      parent::disconnect( $conn );
      return $logEntries;
    } catch ( PDOException $e ) {
      parent::disconnect( $conn );
      die( "Query failed: " . $e->getMessage() );
    }
  }

  public function record() {
    $conn = parent::connect();
    $sql = "SELECT * FROM " . TBL_ACCESS_LOG . " WHERE memberId = :memberId AND pageUrl = :pageUrl";

    try {
      $st = $conn->prepare( $sql );
      $st->bindValue( ":memberId", $this->data["memberId"], PDO::PARAM_INT );
      $st->bindValue( ":pageUrl", $this->data["pageUrl"], PDO::PARAM_STR );
      $st->execute();

      if ( $st->fetch() ) {
        $sql = "UPDATE " . TBL_ACCESS_LOG . " SET numVisits = numVisits + 1 WHERE memberId = :memberId AND pageUrl = :pageUrl";
        $st = $conn->prepare( $sql );
        $st->bindValue( ":memberId", $this->data["memberId"], PDO::PARAM_INT );
        $st->bindValue( ":pageUrl", $this->data["pageUrl"], PDO::PARAM_STR );
        $st->execute();
      } else {
        $sql = "INSERT INTO " . TBL_ACCESS_LOG . " ( memberId, pageUrl, numVisits ) VALUES ( :memberId, :pageUrl, 1 )";
        $st = $conn->prepare( $sql );
        $st->bindValue( ":memberId", $this->data["memberId"], PDO::PARAM_INT );
        $st->bindValue( ":pageUrl", $this->data["pageUrl"], PDO::PARAM_STR );
        $st->execute();
      }

      parent::disconnect( $conn );
    } catch ( PDOException $e ) {
      parent::disconnect( $conn );
      die( "Query failed: " . $e->getMessage() );
    }
  }

  public static function deleteAllForMember( $memberId ) {
    $conn = parent::connect();
    $sql = "DELETE FROM " . TBL_ACCESS_LOG . " WHERE memberId = :memberId";

    try {
      $st = $conn->prepare( $sql );
      $st->bindValue( ":memberId", $memberId, PDO::PARAM_INT );
      $st->execute();
      parent::disconnect( $conn );
    } catch ( PDOException $e ) {
      parent::disconnect( $conn );
      die( "Query failed: " . $e->getMessage() );
    }
  }
  
  function referencedDate($dateExpire)
	{
		// Converted the date to integer
		$dateExpire = intval($dateExpire);
	
		// Calculated the distance
		$distSeconds = time() - $dateExpire;
	
		// The function works towards past and future, just changes 'ago' for 'in'
		$sentence = ($distSeconds >= 0) ? 'Expired %s ago' : 'Expired in %s';
		$distSeconds = abs($distSeconds);
	
		// Searched the larger way to show the results (among seconds, hours and minutes)
		// If seconds/60 is more than one, we'll show minutes, otherwise we'll show seconds
		if ($distSeconds / 60 >= 1)		{
			
			$distMinutes = ceil($distSeconds / 60);
			
			// If minutes/60 is more than one, we'll show hours, otherwise  we'll show minutes
			if ($distMinutes / 60 >= 1)
			{
				$distHours = ceil($distMinutes / 60);
				
				// If hours/24 is more than one, we'll show days, otherwise  we'll show hours
				if ($distHours / 24 >= 1)
				{
					$distDays = round($distHours / 24);
	
					// If days/30 is more than one, we'll show months or years, otherwise  we'll show days
					$distMonthsReal = $distDays / 30;
					if ($distMonthsReal >= 1)  //por ejemplo $distMonthsReal=32/30=1.06
					{
						// If months/12 is more than one, we'll show years, otherwise  we'll show months
						$distYearsReal = $distMonthsReal / 12; //$distYearsReal=1.06/12=0.088
						if ($distYearsReal >= 1)
						{
							$distYearsRounded = floor($distYearsReal);
							//if after applying floor(), years equals 1, it's equal or more than a year, but less than 2
							if ($distYearsRounded == 1)
							{
								if ($distYearsRounded < $distYearsReal)
								return sprintf($sentence, 'more than a year');
								else
								return sprintf($sentence, " ".'a year');
							}
							//else, it's X years
							else
							{
								return sprintf($sentence, $distYearsRounded." ".'years');
							}
						}
						// Months/12 is less than one, so we won't show years, but months
						else
						{
							$distMonthsRounded = floor($distMonthsReal); 
							//if after applying floor(), months equals 1, it's equal or more than a month, but less than 2
							if ($distMonthsRounded == 1)
							{
								if ($distMonthsRounded < $distMonthsReal)
								return sprintf($sentence, " ".'more than a month');
								else
								return sprintf($sentence, " ".'a month');
							}
							//else, it's X months
							else
							{
								return sprintf($sentence, $distMonths.' '.'months');
							}
						}
					}
					// Days/30 is less than one, so we won't show months, but days
					elseif ($distDays > 1)
					{
						return sprintf($sentence, $distDays.' '.'days');
					}
					else
					{
						return sprintf($sentence, ' '.'a day');
					}
				}
				// Hours/24 is less than one, so we won't show days, but hours
				elseif ($distHours > 1)
				{
					return sprintf($sentence, ' '.$distHours.' '.'hours');
				}
				else
				{
					return sprintf($sentence, ' '.'an hour');
				}
			}
			// Minutes/60 is less than one, so we won't show hours, but minutes
			elseif ($distMinutes > 1)
			{
				return sprintf($sentence, ' '.$distMinutes.' '.'minutes');
			}
			else
			{
				return sprintf($sentence, ' '.'a minute');
			}
		}
		// Seconds/60 is less than one, so we won't show minutes, but seconds
		elseif ($distSeconds > 1)
		{
			return sprintf($sentence, ' '.$distSeconds .' '.'seconds');
		}
	
		// By default, a second ago /in a second
		return sprintf($sentence, ' '.'a second');
	}

}

?>
