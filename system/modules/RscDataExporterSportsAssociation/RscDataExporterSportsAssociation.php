<?php if (!defined('TL_ROOT')) die('You cannot access this file directly!');

/**
 * Contao Open Source CMS
 * Copyright (C) 2005-2011 Leo Feyer
 *
 * Formerly known as TYPOlight Open Source CMS.
 *
 * This program is free software: you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation, either
 * version 3 of the License, or (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
 * Lesser General Public License for more details.
 * 
 * You should have received a copy of the GNU Lesser General Public
 * License along with this program. If not, please visit the Free
 * Software Foundation website at <http://www.gnu.org/licenses/>.
 *
 * PHP version 5
 * @copyright  Cliff Parnitzky 2012
 * @author     Cliff Parnitzky
 * @package    RscDataExporterSportsAssociation
 * @license    LGPL
 */

/**ö
 * Class RscDataExporterSportsAssociation
 *
 * The exporter of member data for the sports association.
 * @copyright  Cliff Parnitzky 2012
 * @author     Cliff Parnitzky
 * @package    Controller
 */
class RscDataExporterSportsAssociation extends AbstractDataExporter {
	/**
	 * Create the export file
	 */
	public function createExportFile($objConfig) {
		$ageGroups = array(
			"Von 0 bis 6" => array("min" => 0, "max" => 6),
			"Von 7 bis 14" => array("min" => 7, "max" => 14),
			"Von 15 bis 18" => array("min" => 15, "max" => 18),
			"Von 19 bis 26" => array("min" => 19, "max" => 26),
			"Von 27 bis 40" => array("min" => 27, "max" => 40),
			"Von 41 bis 60" => array("min" => 41, "max" => 60),
			"Von 61" => array("min" => 60, "max" => 100)
		);
		$members = $this->getMembers($ageGroups);
		
		$objFile = $this->createFile($objConfig, "Mitgliederstatistik_LSB_" . date("Y"), 'csv');
		$objFile->write("");
		
		$this->addHeaderData($objFile, $members);
		
		$objFile->append("Jahrgangsstatistik");
		$this->addAssociationStatisticByYear($objFile, $members, array("RSC - Mitglied"), "Gesamt");
		$this->addAssociationStatisticByYear($objFile, $members, array("Verband - RSVN"), "Radsport");
		$this->addAssociationStatisticByYear($objFile, $members, array("Verband - TVN"), "Triathlon");
		$this->addAssociationStatisticByYear($objFile, $members, array("Verband - RSVN", "Verband - TVN"), "Radsport & Triathlon");

		$this->addPageC($objFile, $members);
		
		$objFile->append("Altersgruppenstatistik");
		$this->addAssociationStatisticByAgeGroup($objFile, $members, $ageGroups, array("RSC - Mitglied"), "Gesamt");
		$this->addAssociationStatisticByAgeGroup($objFile, $members, $ageGroups, array("Verband - RSVN"), "Radsport");
		$this->addAssociationStatisticByAgeGroup($objFile, $members, $ageGroups, array("Verband - TVN"), "Triathlon");
		$this->addAssociationStatisticByAgeGroup($objFile, $members, $ageGroups, array("Verband - RSVN", "Verband - TVN"), "Radsport & Triathlon");
		
		$this->addFooterData($objFile, $members, $ageGroups);

		$objFile->close();

		return $objFile->value;
	}
	
	private function getMembers($ageGroups) {
		$this->import("Database");
		
		$dbResult = $this->Database->prepare("SELECT m.id, m.dateOfBirth, m.gender, GROUP_CONCAT(mg.name SEPARATOR ',') AS mgroups FROM tl_member m JOIN tl_member_to_group m2g ON m2g.member_id = m.id JOIN tl_member_group mg ON mg.id = m2g.group_id GROUP BY m.id")
										  ->execute();
		
		$members = array();
		while ($dbResult->next()) {
			$mgroups = explode(",", $dbResult->mgroups);
			if (is_numeric($dbResult->dateOfBirth) && in_array("RSC - Mitglied", $mgroups)) {
				$members[date("Ymd", $dbResult->dateOfBirth) . "_" . $dbResult->id] = array(
					'birthYear'   => date("Y", $dbResult->dateOfBirth),
					'ageGroup'    => $this->getAgeGroup($dbResult->dateOfBirth, $ageGroups),
					'gender'      => $dbResult->gender,
					'groups'      => $mgroups
				);
			}
		}
		
		return $members;
	}
	
	/**
	 * Determine the age group for the given date of birth
	 */
	private function getAgeGroup($dateOfBirth, $ageGroups) {
		$age = floor((date("Ymd") - date("Ymd", $dateOfBirth)) / 10000);
		
		foreach ($ageGroups as $k=>$v) {
			if ($age >= $v["min"] && $age <= $v["max"]) {
				return $k;
			}
		}
		return "";
	}
	
	/**
	 * Creating header info
	 */
	private function addHeaderData($objFile, $members) {
		$males = 0;
		$females = 0;
		
		foreach ($members as $k=>$member) {
			if ($member["gender"] == "male") {
				$males++;
			} else if ($member["gender"] == "female") {
				$females++;
			}
		}
		
		$objFile->append("LandesSportBund Niedersachsen e.V.");
		$objFile->append(date("d.m.Y"));
		$objFile->append("");
		$objFile->append("Vereinsdaten");
		$objFile->append("EDV Nr.:;303290157");
		$objFile->append("Name:;Radsportclub Lüneburg von 1991 e.V.");
		$objFile->append("Kurzame:;RSC Lüneburg");
		$objFile->append("e.V.:;Ja");
		$objFile->append("Grundungsjahr:;1991");
		$objFile->append("");
		$objFile->append("Erfassungsjahr:;" . date("Y"));
		$objFile->append("");
		$objFile->append("Gesamtmitglieder männlich:;" . $males);
		$objFile->append("Gesamtmitglieder weiblich:;" . $females);
		$objFile->append("Summe:;" . ($males + $females));
		$objFile->append("");
		$objFile->append("Mitglieder Sparten männlich:;" . $males);
		$objFile->append("Mitglieder Sparten weiblich:;" . $females);
		$objFile->append("Summe Sparten:;" . ($males + $females));
		$objFile->append("");
		$objFile->append("");
		$objFile->append("");
		$objFile->append("");
	}
	
	/**
	 * Creating the association statistic
	 */
	private function addAssociationStatisticByYear($objFile, $members, $association, $associationName) {
		ksort($members);

		$males = 0;
		$actMales = 0;
		$females = 0;
		$actFemales = 0;
		
		$objFile->append("Fachverband;" . $associationName);
		$objFile->append("Jahrgang;männlich;weiblich;gesamt");

		$actYear = 0;
		foreach ($members as $k=>$member) {
			if (count(array_diff($association, $member["groups"])) == 0) {
				if ($actYear != $member["birthYear"]) {
					if ($actYear > 0) {
						$objFile->append($actYear . ";" . $actMales . ";" . $actFemales . ";" . ($actMales + $actFemales));
					}
					
					// control break
					$actYear = $member["birthYear"];
					$actMales = 0;
					$actFemales = 0;
				}

				if ($member["gender"] == "male") {
					$males++;
					$actMales++;
				} else if ($member["gender"] == "female") {
					$females++;
					$actFemales++;
				}
			}
		}
		if ($actYear > 0) {
			$objFile->append($actYear . ";" . $actMales . ";" . $actFemales . ";" . ($actMales + $actFemales));
		}

		$objFile->append("Summe;" . $males . ";" . $females . ";" . ($males + $females));
		$objFile->append("");
	}
	
	/**
	 * Adding page C
	 */
	private function addPageC($objFile, $members) {
		$objFile->append("Seite C - Mitglieder ohne Zuordnung zu Landesfachverbänden");
		$objFile->append("zu erwartende Kosten:");
		$objFile->append(";Anzahl;Kosten in Euro");
		$objFile->append("Kinder/Jugendliche:;0x2.00;0");
		$objFile->append("Erwachsene:;0x3.00;0");
		$objFile->append("Summe:;;0");
		$objFile->append("");
	}
	
	/**
	 * Creating the association statistic
	 */
	private function addAssociationStatisticByAgeGroup($objFile, $members, $ageGroups, $association, $associationName) {
		krsort($members);
		
		$males = 0;
		$actMales = 0;
		$females = 0;
		$actFemales = 0;
		
		$objFile->append("Fachverband;" . $associationName);
		$objFile->append("Altersgruppe;männlich;weiblich;gesamt");
		
		$ageGroupNames = array_keys($ageGroups);

		$ageGroupCounter = 0;
		$actAgeGroup = $ageGroupNames[$ageGroupCounter++];
		foreach ($members as $k=>$member) {
			if (count(array_diff($association, $member["groups"])) == 0) {
				if ($actAgeGroup != $member["ageGroup"]) {
					if (strlen($actAgeGroup) > 0) {
						$objFile->append($actAgeGroup . ";" . $actMales . ";" . $actFemales . ";" . ($actMales + $actFemales));
					}
					
					// control break
					$actAgeGroup = $ageGroupNames[$ageGroupCounter++];
					$actMales = 0;
					$actFemales = 0;
				}

				if ($member["gender"] == "male") {
					$males++;
					$actMales++;
				} else if ($member["gender"] == "female") {
					$females++;
					$actFemales++;
				}
			}
		}
		if (strlen($actAgeGroup) > 0) {
			$objFile->append($actAgeGroup . ";" . $actMales . ";" . $actFemales . ";" . ($actMales + $actFemales));
		}

		$objFile->append("Summe;" . $males . ";" . $females . ";" . ($males + $females));
		$objFile->append("");
	}
	
	/**
	 * Adding some footer data
	 */
	private function addFooterData($objFile, $members, $ageGroups) {
		$objFile->append("Altersklassen;von;bis");
		foreach ($ageGroups as $k=>$v) {
			$objFile->append($k . ";" . $v["min"] . ";" . $v["max"]);
		}
		$objFile->append("");
	}
}

?>