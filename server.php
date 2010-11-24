<?php
/**
 *
 * This file is part of Open Library System.
 * Copyright � 2009, Dansk Bibliotekscenter a/s,
 * Tempovej 7-11, DK-2750 Ballerup, Denmark. CVR: 15149043
 *
 * Open Library System is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Open Library System is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with Open Library System.  If not, see <http://www.gnu.org/licenses/>.
*/

define(DEBUG_ON, FALSE);

require_once("OLS_class_lib/webServiceServer_class.php");
require_once "OLS_class_lib/z3950_class.php";

class openRuth extends webServiceServer {

  protected $errs;

  public function __construct(){
    webServiceServer::__construct('openruth.ini');
    $this->errs = array("1" => "unknown userId", 
                        "3" => "amount is not the full amount", 
                        "1001" => "already on loan by user", 
                        "1002" => "already reserved by user", 
                        "1003" => "no copies available for reservation", 
                        "1004" => "ordering not allowed for this user", 
                        "1005" => "loan not allowed for this user category", 
                        "1006" => "loan not allowed, user too young", 
                        "1007" => "unspecified error, order not possible", 
                        "1008" => "system error", 
                        "1010" => "system error", 
                        "1030" => "rejected", 
                        "1020" => "booking, not cancelled", 
                        "1030" => "rejected", 
                        "1031" => "reserved",
                        "1032" => "booked",
                        "1033" => "copy reserved",
                        "1034" => "user is blocked",
                        "1035" => "copy not on loan by user",
                        "1036" => "copy not on loan",
                        "1037" => "copy does not exist",
                        "1038" => "ILL, not renewable",
                        "1050" => "ILL, not found",
                        "1051" => "system error",
                        "1052" => "ILL, not cancelled",
                        "1101" => "no agencyId supplied", 
                        "1102" => "unknown agencyId",
                        "1103" => "unknown userId",
                        "1104" => "wrong pin code",
                        "1123" => "no itemId, titlePartNo or bookingId supplied",
                        "1110" => "overbooking",
                        "1111" => "counter does not exist",
                        "1112" => "bookingStartDate must be before bookingEndDate",
                        "1113" => "bookingTotal Count must be 1 or more",
                        "1114" => "normal period of booking exceeded",
                        "1115" => "undefined error",
                        "1116" => "number of fetched copies exceeds number of ordered copies",
                        "1120" => "undefined error",
                        "1135" => "undefined error");
  }


/*
 * Agency Information
 * ==================
 *
 *   agencyCounters
 *   holdings
 */

  /** \brief Fetch agencycounter for a given Agency
   *
   * @param $param - agencyId
   * @return Agencycounter information according to the xsd
   *
   */

  function agencyCounters($param) { 
    if (!$this->aaa->has_right("openruth", 500))
      $res->agencyError->_value = "authentication_error";
    else {
      $targets = $this->config->get_value("ruth", "ztargets");
      $agencyId = $this->strip_agency($param->agencyId->_value);
      if ($tgt = $targets[$agencyId]) {
        $z = new z3950();
        $z->set_target($tgt["host"]);
        $z->set_database($tgt["database"]."-libraryid");
        $z->set_authentication($tgt["authentication"]);
        $z->set_syntax("xml");
        $z->set_element("default");
        $rpn = "@attrset 1.2.840.10003.3.1000.105.3 @attr 1=1 %s";
        $z->set_rpn(sprintf($rpn, $agencyId));
        $this->watch->start("zsearch");
        $hits = $z->z3950_search($tgt["timeout"]);
        $this->watch->stop("zsearch");
        if ($err = $z->get_errno()) {
          $res->userError->_value = "cannot reach local system - (" . $err . ")";
        } elseif (empty($hits))
          $res->userError->_value = "No counters found";
        else {
          $this->watch->start("zrecord");
          $rec = $z->z3950_record();
          $this->watch->stop("zrecord");
          //print_r($rec);
          $dom = new DomDocument();
          $dom->preserveWhiteSpace = false;
          if ($dom->loadXML($rec)) {
            $bos = &$dom->getElementsByTagName("BorrowerStatus")->item(0);
            $trans = array(
              array("from" => "LibraryNo", "to" => "agencyId"),
              array("from" => "LibraryName", "to" => "agencyName"),
              array("from" => "ResActivePeriode", "to" => "orderActivePeriod"));
            $this->move_tags($bos, $res->agencyCounters->_value, $trans);
            //$sc = &$res->agencyCounterInfo->_value;
            $sc = &$res->agencyCounters->_value;
            $trans = array(
              array("from" => "ServiceCounter", "to" => "agencyCounter"),
              array("from" => "ServiceCounterName", "to" => "agencyCounterName"),
              array("from" => "DefaultServiceCounter", "to" => "agencyDefaultCounter", "bool" => "1"));
            foreach ($bos->getElementsByTagName("ServiceCounterInfo") as $info)
              $this->move_tags($info, $sc->agencyCounterInfo[]->_value, $trans);
          } else
            $res->userError->_value = "cannot decode answer";
        }
      } else
        $res->userError->_value = "unknown agencyId";
    }

    $ret->agencyCountersResponse->_value = $res;
    //var_dump($param); var_dump($res); die();
    return $ret;
  }

  /** \brief Fetch holdingsinfo 
   *
   * @param $param - agencyId and one or more itemIds
   * @return holdings information according to the xsd
   *
   */
  function holdings($param) { 
    if (!$this->aaa->has_right("openruth", 500))
      $res->agencyError->_value = "authentication_error";
    else {
      $targets = $this->config->get_value("ruth", "ztargets");
      $agencyId = $this->strip_agency($param->agencyId->_value);
      if ($tgt = $targets[$agencyId]) {
        $z = new z3950();
        $z->set_target($tgt["host"]);
        $z->set_database($tgt["database"]."-xmllocations");
        $z->set_authentication($tgt["authentication"]);
        $z->set_syntax("xml");
        $z->set_element("mm_id");
        $rpn = "@attrset 1.2.840.10003.3.1000.105.3 @and @attr 1=2 %s @attr 1=1 %s";
        if (is_array($param->itemId))
          foreach ($param->itemId as $pid) $pids[] = $pid->_value;
        else
          $pids[] =$param->itemId->_value;
        foreach ($pids as $pid) {
          $z->set_rpn(sprintf($rpn, $pid, $agencyId));
          $this->watch->start("zsearch");
          $hits = $z->z3950_search($tgt["timeout"]);
          $this->watch->stop("zsearch");
//echo "hits: " . $hits . "\n";
          if ($hits > 1)
            verbose::log(ERROR, "holdings(" . __LINE__ . "):: More than one hits searching for id: " . $pid . " and agency: " . $agencyId);
//echo "err: " . $z->get_errno() . "\n";
          if ($err = $z->get_errno()) {
            $res->agencyError->_value = "cannot reach local system - (" . $err . ")";
          } elseif (empty($hits))
            $res->agencyError->_value = "No holdings found";
          else {
            $this->watch->start("zrecord");
            $holdings = $z->z3950_record(1);
            $this->watch->stop("zrecord");
//echo "holdings: /" . $holdings . "/\n"; die();
            $dom = new DomDocument();
            $dom->preserveWhiteSpace = false;
            if ($dom->loadXML($holdings)) {
              foreach ($dom->getElementsByTagName("HOLDING") as $hold) {
                $trans = array(
                  array("from" => "IDNR", "to" => "itemId"),
                  array("from" => "PIFNOTE", "to" => "holdingNote"));
                $this->move_tags($hold, $res_hold->_value, $trans);
                foreach ($hold->getElementsByTagName("LIBRARY") as $lib) {
                  if ($lib->getElementsByTagName("LIBRARYNO")->item(0)->nodeValue == $agencyId) {
                    $trans = array(
                      array("from" => "LIBRARYNO", "to" => "agencyId"),
                      array("from" => "LIBRARYNAME", "to" => "agencyName"),
                      array("from" => "LIBRARYLONGNAME", "to" => "agencyFullName"),
                      array("from" => "ATHOME", "to" => "itemAvailability", "enum" => array("0" => "no copies exist", "1" => "no copies for loan", "2" => "no copies available, but item can be reserved", "3" => "copies available for loan and reservation")));
                    $this->move_tags($lib, $res_hold->_value->agencyHoldings->_value, $trans);
                    foreach ($lib->getElementsByTagName("PERIODICA") as $peri) {
                      $trans = array(
                        array("from" => "TITLEPARTNO", "to" => "itemSerialPartId"),
                        array("from" => "VOLUME", "to" => "itemSerialPartVolume"),
                        array("from" => "NUMBER", "to" => "itemSerialPartIssue"),
                        array("from" => "RESERVATIONS", "to" => "ordersCount"),
                        array("from" => "BOOKINGS", "to" => "bookingsCount"),
                        array("from" => "EXPECTEDDISPATCHDATE", "to" => "itemExpectedAvailabilityDate", "date" => "swap"));
                      $this->move_tags($peri, $res_item->_value, $trans);
                      if (isset($res_item)) {
                        foreach ($peri->childNodes as $location) {
                          if ($location->tagName == "LOCATION")
                            $res_loc = &$res_item->_value->itemLocation[]->_value;
                          elseif ($location->tagName == "LOCATIONCOMMING")
                            $res_loc = &$res_item->_value->itemComingLocation[]->_value;
                          else
                            continue;
                          $trans = array(
                            array("from" => "MMCOLLECTION", "from_attr" => "BOOKINGALLOWED", "to" => "bookingAllowed", "bool" => "y"),
                            array("from" => "MMCOLLECTION", "from_attr" => "RESERVATIONALLOWED", "to" => "orderAllowed", "bool" => "y"));
                          $this->move_tags($location, $res_loc, $trans);
                          $trans = array(
                            array("from" => "MMCOLLECTION", "to" => "agencyCollectionCode"),
                            array("from" => "MMCOLLECTION", "from_attr" => "CODE", "to" => "agencyCollectionName"));
                          $this->move_tags($location, $res_loc->agencyCollectionId->_value, $trans);
                          $trans = array(
                            array("from" => "BRANCH", "from_attr" => "CODE", "to" => "agencyBranchCode"),
                            array("from" => "BRANCH", "to" => "agencyBranchName"));
                          $this->move_tags($location, $res_loc->agencyBranchId->_value, $trans);
                          $trans = array(
                            array("from" => "DEPARTMENT", "to" => "agencyDepartmentCode"),
                            array("from" => "DEPARTMENT", "from_attr" => "CODE", "to" => "agencyDepartmentName"));
                          $this->move_tags($location, $res_loc->agencyDepartmentId->_value, $trans);
                          $trans = array(
                            array("from" => "PLACEMENT", "to" => "agencyPlacementCode"),
                            array("from" => "PLACEMENT", "from_attr" => "CODE", "to" => "agencyPlacementName"));
                          $this->move_tags($location, $res_loc->agencyPlacementId->_value, $trans);
                          $trans = array(
                            array("from" => "HOME", "to" => "copiesAvailableCount"),
                            array("from" => "TOTAL", "to" => "copiesCount"),
                            array("from" => "DELIVERYDATE", "to" => "itemExpectedDeliveryDate", "date" => "swap"));
                          $this->move_tags($location, $res_loc, $trans);
                          unset($res_loc);
                        }
                        $res_hold->_value->itemHoldings[] = $res_item;
                        unset($res_item);
                      }
                    }
                  }
                }
                $res->holding[] = $res_hold;
//print_r($res_hold);
                unset($res_hold);
              }
            } else
              $res->agencyError->_value = "cannot decode answer";
          }
        }
      } else
        $res->agencyError->_value = "unknown agencyId";
    }

    $ret->holdingsResponse->_value = $res;
    //var_dump($param); print_r($res); die();
    return $ret;
  }



/*
 * booking 
 * =======
 *   bookingInfo
 *   bookItem (book) 
 *   updateBooking 
 *   cancelBooking 
 */

  /** \brief Fetch info for a booking
   *
   * @param $param - agency and either itemId (+serialId) or bookingId
   * @return Information about the booking
   *
   */
  function bookingInfo($param) { 
    if (!$this->aaa->has_right("openruth", 500))
      $res->bookingError->_value = "authentication_error";
    else {
      $targets = $this->config->get_value("ruth", "ztargets");
      $agencyId = $this->strip_agency($param->agencyId->_value);
      if ($tgt = $targets[$agencyId]) {
        $z = new z3950();
        $z->set_target($tgt["host"]);
        $z->set_database($tgt["database"]."-bookingprofile");
        $z->set_authentication($tgt["authentication"]);
        $z->set_syntax("xml");
        $z->set_element("default");
        $rpn = "@attrset 1.2.840.10003.3.1000.105.3 @and @attr 1=1 $agencyId ";
        if ($param->bookingId->_value)
          $rpn .= "@attr 1=7 \"" . $param->bookingId->_value . "\"";
        elseif ($param->serialPartId->_value)
          $rpn .= "@and @attr 1=2 " . $param->itemId->_value . " @attr 1=3 " . $param->serialPartId->_value;
        else
          $rpn .= "@and @attr 1=2 " . $param->itemId->_value . " @attr 1=3 0";
        $z->set_rpn($rpn);
        $this->watch->start("zsearch");
        $hits = $z->z3950_search($tgt["timeout"]);
        $this->watch->stop("zsearch");
//echo "hits: " . $hits . "\n";
//echo "err: " . $z->get_errno() . "\n";
        if ($err = $z->get_errno()) {
          $res->bookingError->_value = "cannot reach local system - (" . $err . ")";
        } elseif (empty($hits))
          $res->bookingError->_value = "No booking found";
        else {
          $this->watch->start("zrecord");
          $booking = $z->z3950_record(1);
          $this->watch->stop("zrecord");
//echo "booking: /" . $booking . "/\n";
          $dom = new DomDocument();
          $dom->preserveWhiteSpace = false;
          if ($dom->loadXML($booking)) {
            $trans = array(
              array("from" => "Total", "to" => "bookingInfoTotalCount"),
              array("from" => "BookingNote", "to" => "bookingNote"));
            foreach ($dom->getElementsByTagName("BookingProfile") as $bp)
              $this->move_tags($bp, $res->bookingInfo->_value, $trans);
            foreach ($dom->getElementsByTagName("BookingChange") as $bc) {
              $rbc->bookingChangeCount->_value = $bc->nodeValue;
              $rbc->bookingChangeDate->_value = $bc->getAttribute("D");
              $res->bookingInfo->_value->bookingChange[]->_value = $rbc;
              unset($rbc);
            }
          } else {
            verbose::log(ERROR, "order (" . __LINE__ . ") loadXML error of: " . $xml_ret["xmlUpdateDoc"]);
            $res->bookingError->_value = "undefined error";
          }
        }
      } else
        $res->bookingError->_value = "unknown agencyId";
    }

    $ret->bookingInfoResponse->_value = $res;
    //var_dump($param); print_r($res); die();
    return $ret;
  }

  /** \brief - book an item
   *
   * @param $param - agencyId, userId, agencyCounter, bookingNote, bookingStartDate, bookingEndDate, bookingTotalCount, itemId, itemSerialPartId,
   * @return 
   *
   */
  function bookItem($param) { 
    if (!$this->aaa->has_right("openruth", 500))
      $res->bookingError->_value = "authentication_error";
    else {
      $agencyId = $this->strip_agency($param->agencyId->_value);
      $targets = $this->config->get_value("ruth", "ztargets");
      if ($tgt = $targets[$agencyId]) {
        $book = &$booking->Booking->_value;
        $book->LibraryNo->_value = $agencyId;
        $book->BorrowerTicketNo->_value = $param->userId->_value;
        $book->BookingNote->_value = $param->bookingNote->_value;
        $book->StartDate->_value = $this->to_zruth_date($param->bookingStartDate->_value);
        $book->EndDate->_value = $this->to_zruth_date($param->bookingEndDate->_value);
        $book->NumberOrdered->_value = $param->bookingTotalCount->_value;
        $book->ServiceCounter->_value = $param->agencyCounter->_value;
        $book->MRID->_value->ID->_value = $param->itemId->_value;
        $book->MRID->_value->TitlePartNo->_value = ($param->itemSerialPartId->_value ? $param->itemSerialPartId->_value : 0);
        $xml = '<?xml version="1.0" encoding="ISO-8859-1" ?'.'>' . utf8_decode($this->objconvert->obj2xml($booking));
        $z = new z3950();
        $z->set_target($tgt["host"]);
        $z->set_database($tgt["database"]."-ophelia");
        $z->set_authentication($tgt["authentication"]);
        $xml_ret = $z->z3950_xml_update($xml, $tgt["timeout"]);
//echo "error: " . $z->get_errno();
//print_r($xml);
//print_r($xml_ret);
        if ($z->get_errno() == 0 && $xml_ret["xmlUpdateDoc"]) {
          $dom = new DomDocument();
          $dom->preserveWhiteSpace = false;
          if ($dom->loadXML($xml_ret["xmlUpdateDoc"])) {
            if ($err = $dom->getElementsByTagName("ErrorResponse")->item(0)) {
              verbose::log(ERROR, "book (" . __LINE__ . ") errno: " . $err->getAttribute("Err"));
              if (!($res->bookingError->_value = $this->errs[$err->getAttribute("Err")])) 
                $res->bookingError->_value = "unspecified error (" . $err->getAttribute("Err") . "), order not possible";
            } elseif ($err = $dom->getElementsByTagName("Error")->item(0)->nodeValue) {
              verbose::log(ERROR, "book (" . __LINE__ . ") errno: " . $err);
              if (!($res->bookingError->_value = $this->errs[$err])) 
                $res->bookingError->_value = "unspecified error (" . $err . "), order not possible";
            } else {
              $res->bookingOk->_value->bookingId->_value = $dom->getElementsByTagName("BookingID")->item(0)->nodeValue;
              if ($sd = $this->from_zruth_date($dom->getElementsByTagName("StartDate")->item(0)->nodeValue))
                $res->bookingOk->_value->bookingStartDate->_value = $sd;
              else
                $res->bookingOk->_value->bookingStartDate->_value = $param->bookingStartDate->_value;
              if ($ed = $this->from_zruth_date($dom->getElementsByTagName("EndDate")->item(0)->nodeValue))
                $res->bookingOk->_value->bookingEndDate->_value = $ed;
              else
                $res->bookingOk->_value->bookingEndDate->_value = $param->bookingEndDate->_value;
            }
          } else {
            verbose::log(ERROR, "book (" . __LINE__ . ") loadXML error of: " . $xml_ret["xmlUpdateDoc"]);
            $res->bookingError->_value = "system error";
          }
        } else {
          verbose::log(ERROR, "book (" . __LINE__ . ") z-errno: " . $z->get_error_string());
          $res->bookingError->_value = "system error";
        }
      } else
        $res->bookingError->_value = "unknown agencyId";
    }

    $ret->bookItemResponse->_value = $res;
    //var_dump($param); print_r($res); die();
    return $ret;
  }

  /** \brief Update an existing booking
   *
   * @param $param - agencyId, bookingId, agencyCounter, bookingNote, bookingStartDate, bookingEndDate, bookingsCount
   * @return 
   *
   */
  function updateBooking($param) { 
    if (!$this->aaa->has_right("openruth", 500))
      $res->bookingError->_value = "authentication_error";
    else {
      $agencyId = $this->strip_agency($param->agencyId->_value);
      $targets = $this->config->get_value("ruth", "ztargets");
      if ($tgt = $targets[$agencyId]) {
        $book = &$booking->BookingUpdate->_value;
        $book->LibraryNo->_value = $agencyId;
        $book->DisposalID->_value = $param->bookingId->_value;
        $book->BookingNote->_value = $param->bookingNote->_value;
        $book->StartDate->_value = $this->to_zruth_date($param->bookingStartDate->_value);
        $book->EndDate->_value = $this->to_zruth_date($param->bookingEndDate->_value);
        $book->NumberOrdered->_value = $param->bookingTotalCount->_value;
        $book->ServiceCounter->_value = $param->agencyCounter->_value;
        $xml = '<?xml version="1.0" encoding="ISO-8859-1" ?'.'>' . utf8_decode($this->objconvert->obj2xml($booking));
        $z = new z3950();
        $z->set_target($tgt["host"]);
        $z->set_database($tgt["database"]."-ophelia");
        $z->set_authentication($tgt["authentication"]);
        $xml_ret = $z->z3950_xml_update($xml, $tgt["timeout"]);
//echo "error: " . $z->get_errno();
//print_r($xml);
//print_r($xml_ret);
        if ($z->get_errno() == 0 && $xml_ret["xmlUpdateDoc"]) {
          $dom = new DomDocument();
          $dom->preserveWhiteSpace = false;
          if ($dom->loadXML($xml_ret["xmlUpdateDoc"])) {
            if ($err = $dom->getElementsByTagName("ErrorResponse")->item(0)) {
              verbose::log(ERROR, "updateBook (" . __LINE__ . ") errno: " . $err->getAttribute("Err"));
              if (!($res->updateOrderError->_value = $this->errs[$err->getAttribute("Err")])) 
                $res->updateOrderError->_value = "unspecified error (" . $err->getAttribute("Err") . "), order not possible";
            } elseif ($err = $dom->getElementsByTagName("Error")->item(0)->nodeValue) {
              verbose::log(ERROR, "updateBook (" . __LINE__ . ") errno: " . $err);
              if (!($res->bookingError->_value = $this->errs[$err])) 
                $res->bookingError->_value = "unspecified error (" . $err . "), order not possible";
            } else {
              $res->bookingOk->_value->bookingId->_value = $param->bookingId->_value;
              if ($sd = $this->from_zruth_date($dom->getElementsByTagName("StartDate")->item(0)->nodeValue))
                $res->bookingOk->_value->bookingStartDate->_value = $sd;
              elseif ($sd = $param->bookingStartDate->_value)
                $res->bookingOk->_value->bookingStartDate->_value = $sd;
              if ($ed = $this->from_zruth_date($dom->getElementsByTagName("EndDate")->item(0)->nodeValue))
                $res->bookingOk->_value->bookingEndDate->_value = $ed;
              elseif ($ed = $param->bookingEndDate->_value)
                $res->bookingOk->_value->bookingEndDate->_value = $ed;
            }
          } else {
            verbose::log(ERROR, "updateBook (" . __LINE__ . ") loadXML error of: " . $xml_ret["xmlUpdateDoc"]);
            $res->bookingError->_value = "system error";
          }
        } else {
          verbose::log(ERROR, "updateBook (" . __LINE__ . ") z-errno: " . $z->get_error_string());
          $res->bookingError->_value = "system error";
        }
      } else
        $res->bookingError->_value = "unknown agencyId";
    }

    $ret->updateBookingResponse->_value = $res;
    //var_dump($param); print_r($res); die();
    return $ret;
  }

  /** \brief Delete a booking
   *
   * @param $param - agencyId, bookingId
   * @return 
   *
   */
  function cancelBooking($param) { 
    if (!$this->aaa->has_right("openruth", 500))
      $res->bookingError->_value = "authentication_error";
    else {
      $agencyId = $this->strip_agency($param->agencyId->_value);
      $targets = $this->config->get_value("ruth", "ztargets");
      if ($tgt = $targets[$agencyId]) {
        $book = &$booking->BookingDelete->_value;
        $book->LibraryNo->_value = $agencyId;
        $book->DisposalID->_value = $param->bookingId->_value;
        $xml = '<?xml version="1.0" encoding="ISO-8859-1" ?'.'>' . utf8_decode($this->objconvert->obj2xml($booking));
        $z = new z3950();
        $z->set_target($tgt["host"]);
        $z->set_database($tgt["database"]."-ophelia");
        $z->set_authentication($tgt["authentication"]);
        $xml_ret = $z->z3950_xml_update($xml, $tgt["timeout"]);
//echo "error: " . $z->get_errno();
//print_r($xml);
//print_r($xml_ret);
        if ($z->get_errno() == 0 && $xml_ret["xmlUpdateDoc"]) {
          $dom = new DomDocument();
          $dom->preserveWhiteSpace = false;
          if ($dom->loadXML($xml_ret["xmlUpdateDoc"])) {
            if ($err = $dom->getElementsByTagName("ErrorResponse")->item(0)) {
              verbose::log(ERROR, "cancelBook (" . __LINE__ . ") errno: " . $err->getAttribute("Err"));
              if (!($res->bookingError->_value = $this->errs[$err->getAttribute("Err")])) 
                $res->bookingError->_value = "unspecified error (" . $err->getAttribute("Err") . "), order not possible";
            } else {
              $res->bookingOk->_value->bookingId->_value = $param->bookingId->_value;
            }
          } else {
            verbose::log(ERROR, "cancelBook (" . __LINE__ . ") loadXML error of: " . $xml_ret["xmlUpdateDoc"]);
            $res->bookingError->_value = "system error";
          }
        } else {
          verbose::log(ERROR, "cancelBook (" . __LINE__ . ") z-errno: " . $z->get_error_string());
          $res->bookingError->_value = "system error";
        }
      } else
        $res->bookingError->_value = "unknown agencyId";
    }

    $ret->cancelBookingResponse->_value = $res;
    //var_dump($param); print_r($res); die();
    return $ret;
  }


/*
 * ordersAndRenewal 
 * ================
 *   cancelOrder (DeleteReservation) 
 *   orderItem (ReserveMaterials) 
 *   updateOrder (UpdateOrders) 
 */

  /** \brief Delete a reservation
   *
   * @param $param - agencyId, orderId
   * @return 
   *
   */
  function cancelOrder($param) { 
    if (!$this->aaa->has_right("openruth", 500))
      $res->cancelOrderError->_value = "authentication_error";
    else {
      $agencyId = $this->strip_agency($param->agencyId->_value);
      $targets = $this->config->get_value("ruth", "ztargets");
      if ($tgt = $targets[$agencyId]) {
        $ord = &$order->ReservationDelete->_value;
        $ord->LibraryNo->_value = $agencyId;
        $ord->DisposalID->_value = $param->orderId->_value;
        $xml = '<?xml version="1.0" encoding="ISO-8859-1" ?'.'>' . utf8_decode($this->objconvert->obj2xml($order));
        $z = new z3950();
        $z->set_target($tgt["host"]);
        $z->set_database($tgt["database"]."-ophelia");
        $z->set_authentication($tgt["authentication"]);
        $xml_ret = $z->z3950_xml_update($xml, $tgt["timeout"]);
//echo "error: " . $z->get_errno();
//print_r($xml);
//print_r($xml_ret);
        if ($z->get_errno() == 0 && $xml_ret["xmlUpdateDoc"]) {
          $dom = new DomDocument();
          $dom->preserveWhiteSpace = false;
          if ($dom->loadXML($xml_ret["xmlUpdateDoc"])) {
            if ($err = $dom->getElementsByTagName("ReservationDeleteResponse")->item(0)->nodeValue) {
              verbose::log(ERROR, "cancelOrder (" . __LINE__ . ") errno: " . $err);
              if (!($res->cancelOrderError->_value = $this->errs[$err])) 
                $res->cancelOrderError->_value = "unspecified error (" . $err . "), order not possible";
            } else {
              $res->cancelOrderOk->_value = $param->orderId->_value;
            }
          } else {
            verbose::log(ERROR, "cancelOrder (" . __LINE__ . ") loadXML error of: " . $xml_ret["xmlUpdateDoc"]);
            $res->cancelOrderError->_value = "system error";
          }
        } else {
          verbose::log(ERROR, "cancelOrder (" . __LINE__ . ") z-errno: " . $z->get_error_string());
          $res->cancelOrderError->_value = "system error";
        }
      } else
        $res->cancelOrderError->_value = "unknown agencyId";
    }

    $ret->cancelOrderResponse->_value = $res;
    //var_dump($param); print_r($res); die();
    return $ret;
  }

  /** \brief Create a new reservation
   *
   * @param $param - agencyId, userId, orderNote, orderLastInterestDate, agencyCounter, 
   *                 orderOverRule, orderPriority, orderEachItem, itemId, itemSerialPartId,
   * @return 
   *
   */
  function orderItem($param) { 
    if (!$this->aaa->has_right("openruth", 500))
      $res->orderItemError->_value = "authentication_error";
    else {
      $targets = $this->config->get_value("ruth", "ztargets");
      $agencyId = $this->strip_agency($param->agencyId->_value);
      if ($tgt = $targets[$agencyId]) {
    // build order
        $ord = &$order->Reservation->_value;
        $ord->LibraryNo->_value = $agencyId;
        $ord->BorrowerTicketNo->_value = $param->userId->_value;
        $ord->DisposalNote->_value = $param->orderNote->_value;
        $ord->LastUseDate->_value = $this->to_zruth_date($param->orderLastInterestDate->_value);
        $ord->ServiceCounter->_value = $param->agencyCounter->_value;
        $ord->Override->_value = ($this->xs_true($param->agencyCounter->_value) ? "Y" : "N");
        $ord->Priority->_value = $param->orderPriority->_value;
        // ?????? $ord->DisposalType->_value = $param->xxxx->_value;
        $itemIds = &$param->orderItemId;
        if (is_array($itemIds))
          foreach ($itemIds as $oid) {
            $mrid->ID->_value = $oid->_value->itemId->_value;
            if (!$mrid->TitlePartNo->_value = $oid->_value->itemSerialPartId->_value)
              $mrid->TitlePartNo->_value = 0;
            $ord->MRIDS->_value->MRID[]->_value = $mrid;
          }
        else {
          $mrid->ID->_value = $itemIds->_value->itemId->_value;
          if (!$mrid->TitlePartNo->_value = $itemIds->_value->itemSerialPartId->_value)
            $mrid->TitlePartNo->_value = 0;
          $ord->MRIDS->_value->MRID->_value = $mrid;
        }
        $xml = '<?xml version="1.0" encoding="ISO-8859-1" ?'.'>' . utf8_decode($this->objconvert->obj2xml($order));
        
//print_r($ord);
//print_r($xml);
        
        $z = new z3950();
        $z->set_target($tgt["host"]);
        $z->set_database($tgt["database"]."-ophelia");
        $z->set_authentication($tgt["authentication"]);
        $xml_ret = $z->z3950_xml_update($xml, $tgt["timeout"]);
        if ($z->get_errno() == 0 && $xml_ret["xmlUpdateDoc"]) {
          $dom = new DomDocument();
          $dom->preserveWhiteSpace = false;
          if ($dom->loadXML($xml_ret["xmlUpdateDoc"])) {
            if ($err = &$dom->getElementsByTagName("ErrorResponse")->item(0)) {
              verbose::log(ERROR, "order (" . __LINE__ . ") errno: " . $err->getAttribute("Err") . 
                                  " error: " . $err->nodeValue);
              $res->orderItemError->_value = "unspecified error, order not possible";
            } else {
              // order at least partly ok 
              foreach ($dom->getElementsByTagName("MRID") as $mrid) {
                unset($ir);
                $ir->orderItemId->_value->itemId->_value = $mrid->getAttribute("Id");
                if ($mrid->getAttribute("Tp"))
                  $ir->orderItemId->_value->itemSerialPartId->_value = $mrid->getAttribute("Tp");
                if (!$mrid->nodeValue)
                  $ir->orderItemOk->_value = "true";
                elseif (!($ir->orderItemError->_value = $this->errs[$mrid->nodeValue])) 
                  $ir->orderItemError->_value = "unknown error: " . $mrid->nodeValue;
                $res->orderItem[]->_value = $ir;
              }
            }
          }
        } else {
          verbose::log(ERROR, "order (" . __LINE__ . ") z-errno: " . $z->get_error_string());
          $res->orderItemError->_value = "system error";
        }
//echo "\n";
//print_r($xml_ret);
//print_r("\nError: " . $z->get_errno());
      } else
        $res->orderItemError->_value = "unknown agencyId";
    }

    $ret->orderItemResponse->_value = $res;
    //var_dump($param); var_dump($res); die();
    return $ret;
  }

  /** \brief Update an existing reservation
   *
   * @param $param - agencyId, orderId, orderNote, orderLastInterestDate, agencyCounter
   * @return
   *
   */
  function updateOrder($param) { 
    if (!$this->aaa->has_right("openruth", 500))
      $res->updateOrderError->_value = "authentication_error";
    else {
      $agencyId = $this->strip_agency($param->agencyId->_value);
      $targets = $this->config->get_value("ruth", "ztargets");
      if ($tgt = $targets[$agencyId]) {
        $ord = &$order->ReservationUpdate->_value;
        $ord->LibraryNo->_value = $agencyId;
        $ord->DisposalID->_value = $param->orderId->_value;
        $ord->DisposalNote->_value = $param->orderNote->_value;
        $ord->LastUseDate->_value = $this->to_zruth_date($param->orderLastInterestDate->_value);
        $ord->ServiceCounter->_value = $param->agencyCounter->_value;
        $xml = '<?xml version="1.0" encoding="ISO-8859-1" ?'.'>' . utf8_decode($this->objconvert->obj2xml($order));
        $z = new z3950();
        $z->set_target($tgt["host"]);
        $z->set_database($tgt["database"]."-ophelia");
        $z->set_authentication($tgt["authentication"]);
        $xml_ret = $z->z3950_xml_update($xml, $tgt["timeout"]);
//echo "error: " . $z->get_errno();
//print_r($xml);
//print_r($xml_ret);
        if ($z->get_errno() == 0 && $xml_ret["xmlUpdateDoc"]) {
          $dom = new DomDocument();
          $dom->preserveWhiteSpace = false;
          if ($dom->loadXML($xml_ret["xmlUpdateDoc"])) {
            if ($err = $dom->getElementsByTagName("ErrorResponse")->item(0)) {
              verbose::log(ERROR, "updateOrder (" . __LINE__ . ") errno: " . $err->getAttribute("Err"));
              if (!($res->updateOrderError->_value = $this->errs[$err->getAttribute("Err")])) 
                $res->updateOrderError->_value = "unspecified error (" . $err->getAttribute("Err") . "), order not possible";
            } else {
              $res->updateOrderOk->_value = $param->orderId->_value;
            }
          } else {
            verbose::log(ERROR, "updateOrder (" . __LINE__ . ") loadXML error of: " . $xml_ret["xmlUpdateDoc"]);
            $res->updateOrderError->_value = "system error";
          }
        } else {
          verbose::log(ERROR, "updateOrder (" . __LINE__ . ") z-errno: " . $z->get_error_string());
          $res->updateOrderError->_value = "system error";
        }
      } else
        $res->updateOrderError->_value = "unknown agencyId";
    }

    $ret->updateOrderResponse->_value = $res;
    //var_dump($param); print_r($res); die();
    return $ret;
  }


/*
 * User
 * ===================
 *
 *   renewLoan
 *   updateUserInfo (BorrowerPinMail)
 *   userCheck (BorrowerCheck) 
 *   userPayment (BorrowerPay) 
 *   userStatus (BorrowerStatus) 
 */

  /** \brief Renew a loan
   *
   * @param $param 
   * @return
   *
   */
  function renewLoan($param) { 
    if (!$this->aaa->has_right("openruth", 500))
      $res->renewLoanError->_value = "authentication_error";
    else {
      $agencyId = $this->strip_agency($param->agencyId->_value);
      $targets = $this->config->get_value("ruth", "ztargets");
      if ($tgt = $targets[$agencyId]) {
        $renewal = &$renew->Renewal->_value;
        $renewal->LibraryNo->_value = $agencyId;
        $renewal->BorrowerTicketNo->_value = $param->userId->_value;
        if (is_array($param->copyId))
          foreach ($param->copyId as $copyId)
            $renewal->CopyNos->_value->CopyNo[]->_value = $copyId->_value;
        else
          $renewal->CopyNos->_value->CopyNo->_value = $param->copyId->_value;
        $xml = '<?xml version="1.0" encoding="ISO-8859-1" ?'.'>' . utf8_decode($this->objconvert->obj2xml($renew));
        $z = new z3950();
        $z->set_target($tgt["host"]);
        $z->set_database($tgt["database"]."-ophelia");
        $z->set_authentication($tgt["authentication"]);
        $xml_ret = $z->z3950_xml_update($xml, $tgt["timeout"]);
//echo "error: " . $z->get_errno();
//print_r($xml);
//print_r($xml_ret);
        if ($z->get_errno() == 0 && $xml_ret["xmlUpdateDoc"]) {
          $dom = new DomDocument();
          $dom->preserveWhiteSpace = false;
          if ($dom->loadXML($xml_ret["xmlUpdateDoc"])) {
            if ($err = $dom->getElementsByTagName("BorrowerError")->item(0)->nodeValue) {
              verbose::log(ERROR, "renew (" . __LINE__ . ") errno: " . $err);
              if (!($res->renewLoanError->_value = $this->errs[$err])) 
                $res->renewLoanError->_value = "unspecified error (" . $err . "), order not possible";
            } else {
              foreach ($dom->getElementsByTagName("CopyNoStatus") as $cns) {
                $rl->copyId->_value = $cns->getAttribute("CopyNo");
                if (!$cns->nodeValue)
                  $rl->renewLoanOk->_value = "true";
                elseif (!($rl->renewLoanError->_value = $this->errs[$cns->nodeValue])) 
                  $rl->renewLoanError->_value = "unspecified error (" . $cns->nodeValue . "), order not possible";
                $res->renewLoan[]->_value = $rl;
                unset($rl);
              }
            }
          } else {
            verbose::log(ERROR, "renew (" . __LINE__ . ") loadXML error of: " . $xml_ret["xmlUpdateDoc"]);
            $res->renewLoanError->_value = "system error";
          }
        } else {
          verbose::log(ERROR, "renew (" . __LINE__ . ") z-errno: " . $z->get_error_string());
          $res->renewLoanError->_value = "system error";
        }
      } else
        $res->renewLoanError->_value = "unknown agencyId";
    }

    $ret->renewLoanResponse->_value = $res;
    //print_r($param); print_r($res); die();
    return $ret;
  }

  /** \brief 
   *
   * @param $param agencyId, userId, userPinCode, userPinCodeNew, userEmail, userMobilePhone, 
   *               userRecallNotificationPreference, userOrderReadyNotificationPreference,
   *               userFirstName, userLastName, agencyCounter
   * @return
   *
   */
  function updateUserInfo($param) { 
    if (!$this->aaa->has_right("openruth", 500))
      $res->userError->_value = "authentication_error";
    else {
      $agencyId = $this->strip_agency($param->agencyId->_value);
      $targets = $this->config->get_value("ruth", "ztargets");
      if ($tgt = $targets[$agencyId]) {
        $bor = &$borrower->BorrowerPinMail->_value;
        $bor->LibraryNo->_value = $agencyId;
        $bor->BorrowerTicketNo->_value = $param->userId->_value;
        $bor->OldPinCode->_value = $param->userPinCode->_value;
        if ($param->userPinCodeNew->_value) $bor->NewPinCode->_value = $param->userPinCodeNew->_value;
        if ($param->userEmail) $bor->Email->_value = $param->userEmail->_value;
        if ($param->userMobilePhone) $bor->MobilePhone->_value = $param->userMobilePhone->_value;
        $notifications = array("sms" => "s", "email" => "e", "both" => "b");
        if ($param->userRecallNotificationPreference->_value)
          if (!($bor->UsePreReturnMsg->_value = $notifications[$param->userRecallNotificationPreference->_value]))
            $bor->UsePreReturnMsg->_value = "other";
        if ($param->userOrderReadyNotificationPreference->_value)
          if (!($bor->UseCopyRetainedMsg->_value = $notifications[$param->userOrderReadyNotificationPreference->_value]))
            $bor->UseCopyRetainedMsg->_value = "other";
        if ($param->userFirstName->_value) $bor->FirstName->_value = $param->userFirstName->_value;
        if ($param->userLastName->_value) $bor->FamilyName->_value = $param->userLastName->_value;
        if ($param->agencyCounter->_value) $bor->StandardCounter->_value = $param->agencyCounter->_value;
        $xml = '<?xml version="1.0" encoding="ISO-8859-1" ?'.'>' . utf8_decode($this->objconvert->obj2xml($borrower));
        $z = new z3950();
        $z->set_target($tgt["host"]);
        $z->set_database($tgt["database"]."-ophelia");
        $z->set_authentication($tgt["authentication"]);
        $xml_ret = $z->z3950_xml_update($xml, $tgt["timeout"]);
//echo "error: " . $z->get_errno();
//print_r($xml);
//print_r($xml_ret);
        if ($z->get_errno() == 0 && $xml_ret["xmlUpdateDoc"]) {
          $dom = new DomDocument();
          $dom->preserveWhiteSpace = false;
          if ($dom->loadXML($xml_ret["xmlUpdateDoc"])) {
            if ($err = $dom->getElementsByTagName("ErrorResponse")->item(0)) {
              verbose::log(ERROR, "updateUser (" . __LINE__ . ") errno: " . $err->getAttribute("Err"));
              if (!($res->userError->_value = $this->errs[$err->getAttribute("Err")])) 
                $res->userError->_value = "unspecified error (" . $err->getAttribute("Err") . "), user not updated";
            } elseif ($err = $dom->getElementsByTagName("BorrowerPinMailResponse")->item(0)->nodeValue) {
              verbose::log(ERROR, "updateUser (" . __LINE__ . ") errno: " . $err);
              if (!($res->userError->_value = $this->errs[$err])) 
                $res->userError->_value = "unspecified error (" . $err . "), user not updated";
            } else {
              $res->updateUserInfoOk->_value = "true";
            }
          } else {
            verbose::log(ERROR, "updateUser (" . __LINE__ . ") loadXML error of: " . $xml_ret["xmlUpdateDoc"]);
            $res->userError->_value = "cannot decode answer";
          }
        } else {
          verbose::log(ERROR, "updateUser (" . __LINE__ . ") z-errno: " . $z->get_error_string());
          $res->userError->_value = "cannot reach local system";
        }
      } else
        $res->userError->_value = "unknown agencyId";
    }

    $ret->updateUserInfoResponse->_value = $res;
    //var_dump($param); print_r($res); die();
    return $ret;
  }

  /** \brief Check if user exist
   *
   * @param $param - agencyId, userId, userPinCode
   * @return info about user
   *
   */
  function userCheck($param) { 
    if (!$this->aaa->has_right("openruth", 500))
      $res->agencyError->_value = "authentication_error";
    else {
      $targets = $this->config->get_value("ruth", "ztargets");
      $agencyId = $this->strip_agency($param->agencyId->_value);
      if ($tgt = $targets[$agencyId]) {
        $z = new z3950();
        $z->set_target($tgt["host"]);
        $z->set_database($tgt["database"]."-borrowercheck");
        $z->set_authentication($tgt["authentication"]);
        $z->set_syntax("xml");
        $z->set_element("test");
        $rpn = "@attrset 1.2.840.10003.3.1000.105.3 @and @attr 1=1 %s @and @attr 1=4 %s @attr 1=5 %s";
        $z->set_rpn(sprintf($rpn, $agencyId, $param->userId->_value, $param->userPinCode->_value));
        $this->watch->start("zsearch");
        $hits = $z->z3950_search($tgt["timeout"]);
        $this->watch->stop("zsearch");
//var_dump($hits);
//var_dump($z->get_errno());
        if ($err = $z->get_errno()) {
          $res->userError->_value = "cannot reach local system - (" . $err . ")";
        } elseif (empty($hits))
          $res->userError->_value = "unknown userId";
        else {
          $this->watch->start("zrecord");
          $rec = $z->z3950_record();
          $this->watch->stop("zrecord");
          //print_r($rec);
          $dom = new DomDocument();
          $dom->preserveWhiteSpace = false;
          if ($dom->loadXML($rec)) {
            $chk = &$dom->getElementsByTagName("BorrowerCheck")->item(0);
            $trans = array(
              array("from" => "BorrowerFound", "to" => "userFound", "bool" => "y"),
              array("from" => "PinOk", "to" => "userPinCodeOk", "bool" => "y"),
              array("from" => "Blocked", "to" => "userBlocked", "bool" => "y"),
              array("from" => "Lost", "to" => "userCardLost", "bool" => "y"),
              array("from" => "HasLeft", "to" => "userHasLeft", "bool" => "y"),
              array("from" => "Valid", "to" => "userValid", "bool" => "y"),
              array("from" => "IsInMunicipal", "to" => "userIsInMunicipal", "bool" => "y"),
              array("from" => "ReservationAllowed", "to" => "userOrderAllowed", "bool" => "y"),
              array("from" => "BookingAllowed", "to" => "userBookingAllowed", "bool" => "y"),
              array("from" => "EmailAddress", "to" => "userEmail"),
              array("from" => "BorrowerCat", "to" => "userCategoryCode"),
              array("from" => "BorrowerCatName", "to" => "userCategoryName"),
              array("from" => "StandardCounter", "to" => "agencyCounter"),
              array("from" => "BirthYear", "to" => "userBirthYear"),
              array("from" => "Sex", "to" => "userSex", "enum" => array("b" => "male", "g" => "female", "u" => unknown)),
              array("from" => "userAge", "to" => "Age"));
            $this->move_tags($chk, $res->userCheck->_value, $trans);
          } else
            $res->userError->_value = "cannot decode answer";
        }
      } else
        $res->userError->_value = "unknown agencyId";
    }

    $ret->userCheckResponse->_value = $res;
    //var_dump($param); var_dump($res); die();
    return $ret;
  }

  /** \brief 
   *
   * @param $param - agencyId, userId, fineAmountPaid, userPaymentTransactionId
   * @return 
   *
   */
  function userPayment($param) { 
    if (!$this->aaa->has_right("openruth", 500))
      $res->userPaymentError->_value = "authentication_error";
    else {
      $agencyId = $this->strip_agency($param->agencyId->_value);
      $targets = $this->config->get_value("ruth", "ztargets");
      if ($tgt = $targets[$agencyId]) {
        $pay = &$payment->BorrowerPay->_value;
        $pay->LibraryNo->_value = $agencyId;
        $pay->BorrowerTicketNo->_value = $param->userId->_value;
        $pay->Amount->_value = $param->fineAmountPaid->_value;
        $pay->TransactionID->_value = $param->userPaymentTransactionId->_value;
        $xml = '<?xml version="1.0" encoding="ISO-8859-1" ?'.'>' . utf8_decode($this->objconvert->obj2xml($payment));
        $z = new z3950();
        $z->set_target($tgt["host"]);
        $z->set_database($tgt["database"]."-ophelia");
        $z->set_authentication($tgt["authentication"]);
        $xml_ret = $z->z3950_xml_update($xml, $tgt["timeout"]);
//echo "error: " . $z->get_errno();
//print_r($xml);
//print_r($xml_ret);
        if ($z->get_errno() == 0 && $xml_ret["xmlUpdateDoc"]) {
          $dom = new DomDocument();
          $dom->preserveWhiteSpace = false;
          if ($dom->loadXML('<?xml version="1.0" encoding="ISO-8859-1" ?'.'>'.$xml_ret["xmlUpdateDoc"])) {
            if ($err = $dom->getElementsByTagName("ErrorResponse")->item(0)) {
              verbose::log(ERROR, "userPayment (" . __LINE__ . ") errno: " . $err->getAttribute("Err"));
              if (!($res->userPaymentError->_value = $this->errs[$err->getAttribute("Err")])) 
                $res->userPaymentError->_value = "unspecified error (" . $err->getAttribute("Err") . "), order not possible";
            } else {
              $res->userPaymentOk->_value = "true";
            }
          } else {
            verbose::log(ERROR, "userPayment (" . __LINE__ . ") loadXML error of: " . $xml_ret["xmlUpdateDoc"]);
            $res->userPaymentError->_value = "system error";
          }
        } else {
          verbose::log(ERROR, "userPayment (" . __LINE__ . ") z-errno: " . $z->get_error_string());
          $res->userPaymentError->_value = "system error";
        }
      } else
        $res->userPaymentError->_value = "unknown agencyId";
    }

    $ret->userPaymentResponse->_value = $res;
    //var_dump($param); print_r($res); die();
    return $ret;
  }

  /** \brief 
   *
   * @param $param 
   * @return Information about the user
   *
   */
  function userStatus($param) { 
    if (!$this->aaa->has_right("openruth", 500))
      $res->userError->_value = "authentication_error";
    else {
      $targets = $this->config->get_value("ruth", "ztargets");
      $agencyId = $this->strip_agency($param->agencyId->_value);
      if ($tgt = $targets[$agencyId]) {
        $z = new z3950();
        $z->set_target($tgt["host"]);
        $z->set_database($tgt["database"]."-borrowerstatus");
        $z->set_authentication($tgt["authentication"]);
        $z->set_syntax("xml");
        $z->set_element("default");
        $rpn = "@attrset 1.2.840.10003.3.1000.105.3 @and @attr 1=1 %s @and @attr 1=4 %s @attr 1=5 %s";
        $z->set_rpn(sprintf($rpn, $agencyId, $param->userId->_value, $param->userPinCode->_value));
        $this->watch->start("zsearch");
        $hits = $z->z3950_search($tgt["timeout"]);
        $this->watch->stop("zsearch");
        if ($err = $z->get_errno()) {
          if (!($res->userError->_value = $this->errs[$err])) 
            $res->userError->_value = "cannot reach local system - (" . $err . ")";
        } elseif (empty($hits))
          $res->userError->_value = "unknown userId";
        else {
          $this->watch->start("zrecord");
          $rec = $z->z3950_record();
          $this->watch->stop("zrecord");
          //print_r($rec); die();
          $dom = new DomDocument();
          $dom->preserveWhiteSpace = false;
          if ($dom->loadXML($rec)) {
        // userInfo
            $loaner = &$dom->getElementsByTagName("Loaner")->item(0);
            $ui = &$res->userStatus->_value->userInfo->_value;
            $trans = array(
              array("from" => "FirstName", "to" => "userFirstName"),
              array("from" => "LastName", "to" => "userLastName"),
              array("from" => "Category", "to" => "userCategoryName"),
              array("from" => "Address", "to" => "userAddress"),
              array("from" => "PostCode", "to" => "userPostCode"),
              array("from" => "PostDistrict", "to" => "userCity"),
              array("from" => "Village", "to" => "userVillage"),
              array("from" => "Telephone", "to" => "userTelephone"),
              array("from" => "AttName", "to" => "userAttName"),
              array("from" => "CoName", "to" => "userCoName"),
              array("from" => "Pincode", "to" => "userPinCode"),
              array("from" => "EmailAddress", "to" => "userEmail"),
              array("from" => "StandardCounter", "to" => "agencyCounter"),
              array("from" => "ReservationAllowed", "to" => "userOrderAllowed", "bool" => "y"),
              array("from" => "BookingAllowed", "to" => "userBookingAllowed", "bool" => "y"),
              array("from" => "Penalties", "to" => "userFeesTotal", "decimal" => TRUE),
              array("from" => "MobilePhone", "to" => "userMobilePhone"));
            $this->move_tags($loaner, $ui, $trans);
          // journals maps into userInfo
            $trans = array(
              array("from" => "JournalDate", "to" => "userNoteDate", "date" => "swap"),
              array("from" => "JournalTxt", "to" => "userNoteText"));
            foreach ($dom->getElementsByTagName("Journals") as $journals)
              foreach ($journals->getElementsByTagName("Journal") as $journal)
                $this->move_tags($journal, $ui->userNote[]->_value, $trans);
          // ... and the rest ...
            $trans = array(
              array("from" => "UsePreReturnMsg", "to" => "userRecallNotificationPreference", "enum" => array("e" => "email", "s" => "sms", "b" => "both")),
              array("from" => "UseCopyRetainedMsg", "to" => "userOrderReadyNotificationPreference", "enum" => array("e" => "email", "s" => "sms", "b" => "both")));
            $this->move_tags($loaner, $ui, $trans);

        // fines
            $fi = &$res->userStatus->_value->fees->_value;
            $trans = array(
              array("from" => "ServiceDate", "to" => "feeDate", "date" => "swap"),
              array("from" => "ServiceCounter", "to" => "agencyCounter"),
              array("from" => "Title", "to" => "itemDisplayTitle"),
              array("from" => "Amount", "to" => "feeAmount", "decimal" => TRUE),
              array("from" => "Payed", "to" => "feeAmountPaid"),
              array("from" => "ServiceType", "to" => "feeType", "enum" => array("1" => "first recall", "2" => "second recall", "3" => "third recall", "d" => "reservation", "r" => "reminder", "c" => "compensation", "p" => "penalty", "o" => "other")),
              array("from" => "InvoiceNo", "to" => "feeInvoiceNumber"));
            foreach ($dom->getElementsByTagName("Fines") as $fines)
              foreach ($fines->getElementsByTagName("Fine") as $fine)
                $this->move_tags($fine, $fi->fee[]->_value, $trans);
            

        // loans
            $los = &$res->userStatus->_value->loans->_value;
            $loans = &$dom->getElementsByTagName("Loans")->item(0);
            $trans = array(
              array("from" => "RenewChecked", "to" => "renewAllLoansAllowed", "bool" => "y"));
            $this->move_tags($loans, $los, $trans);
            $status = &$dom->getElementsByTagName("Status");
            $trans = array(
              array("from" => "LoanCat", "to" => "loanCategory"),
              array("from" => "MatAtHome", "to" => "loanCategoryCount"));
            $trans_2 = array(
              array("from" => "RecallType", "to" => "loanRecallType", "enum" => array("0" => "none", "Late" => "late", "Recall1" => "first recall", "Recall2" => "second recall", "Recall3" => "third recall", "Compensation" => "compensation")),
              array("from" => "Number", "to" => "loanRecallTypeCount "));
            foreach ($dom->getElementsByTagName("Status") as $status) {
              foreach ($status->getElementsByTagName("CategoryLoans") as $c_los)
                $this->move_tags($c_los, $los->loanCategories[]->_value, $trans);
              foreach ($status->getElementsByTagName("RecallStatus") as $r_stat)
                $this->move_tags($r_stat, $los->loanRecallTypes[]->_value, $trans_2);
            }
            $trans = array(
              array("from" => "Title", "to" => "itemDisplayTitle"),
              array("from" => "NCIP-Author", "to" => "itemAuthor"),
              array("from" => "NCIP-Title", "to" => "itemTitle"),
              array("from" => "NCIP-PublicationDate", "to" => "itemPublicationYear"),
              array("from" => "CopyNo", "to" => "copyId"),
              array("from" => "LoanDate", "to" => "loanDate", "date" => "swap"),
              array("from" => "ReturnDate", "to" => "loanReturnDate", "date" => "swap"),
              array("from" => "LastRenewal", "to" => "loanLastRenewedDate", "date" => "swap"),
              array("from" => "LoanStatus", "to" => "loanStatus"),
              array("from" => "RecallType", "to" => "loanRecallType", "enum" => array("0" => "none", "Late" => "late", "1" => "first recall", "2" => "second recall", "3" => "third recall", "c" => "compensation")),
              array("from" => "RecallDate", "to" => "loanRecallDate", "date" => "swap"),
              array("from" => "CanRenew", "to" => "loanRenewable", "enum" => array("0" => "renewable", "1" => "not renewable", "2" => "ILL, renewable", "3" => "ILL, not renewable")));
            foreach ($loans->getElementsByTagName("Loan") as $loan)
              $this->move_tags($loan, $los->loan[]->_value, $trans);

        // orders
            $ord = &$res->userStatus->_value->orders->_value;
            $reservations = &$dom->getElementsByTagName("Reservations")->item(0);
            $rsr = &$reservations->getElementsByTagName("ReservationsReady")->item(0);
            $trans = array(
              array("from" => "Title", "to" => "itemDisplayTitle"),
              array("from" => "NCIP-Author", "to" => "itemAuthor"),
              array("from" => "NCIP-Title", "to" => "itemTitle"),
              array("from" => "NCIP-PublicationDate", "to" => "itemPublicationYear"),
              array("from" => "NCIP-UnstructuredHoldingsData", "to" => "itemSerialPartTitle"),
              array("from" => "ServiceCounter", "to" => "agencyCounter"),
              array("from" => "CollectDate", "to" => "orderPickUpDate", "date" => "swap"),
              array("from" => "RetainedDate", "to" => "orderFetchedDate", "date" => "swap"),
              array("from" => "CollectNo", "to" => "orderPickUpId"),
              array("from" => "DisposalID", "to" => "orderId"),
              array("from" => "CreationDate", "to" => "orderDate", "date" => "swap"),
              array("from" => "Arrived", "to" => "orderArrived", "bool" => "true"));
            foreach ($rsr->getElementsByTagName("ReservationReady") as $r)
              $this->move_tags($r, $ord->orderReady[]->_value, $trans);
            $rsnr = &$reservations->getElementsByTagName("ReservationsNotReady")->item(0);
            $trans = array(
              array("from" => "Title", "to" => "itemDisplayTitle"),
              array("from" => "NCIP-Author", "to" => "itemAuthor"),
              array("from" => "NCIP-Title", "to" => "itemTitle"),
              array("from" => "NCIP-PublicationDate", "to" => "itemPublicationYear"),
              array("from" => "NCIP-UnstructuredHoldingsData", "to" => "itemSerialPartTitle"),
              array("from" => "ServiceCounter", "to" => "agencyCounter"),
              array("from" => "CollectDate", "to" => "orderPickUpDate", "date" => "swap"),
              array("from" => "RetainedDate", "to" => "orderFetchedDate", "date" => "swap"),
              array("from" => "CollectNo", "to" => "orderPickUpId"),
              array("from" => "DisposalID", "to" => "orderId"),
              array("from" => "CreationDate", "to" => "orderDate", "date" => "swap"),
              array("from" => "Arrived", "to" => "orderArrived", "bool" => "y"),
              array("from" => "LastUseDate", "to" => "orderLastInterestDate", "date" => "swap"),
              array("from" => "Priority", "to" => "orderPriority", "enum" => array("1" => "express", "2" => "high", "3" => "normal")),
              array("from" => "QueNumber", "to" => "orderQueuePosition"),
              array("from" => "DisposalNote", "to" => "orderNote"),
              array("from" => "DisposalType", "to" => "orderType", "enum" => array("0" => "booking", "1" => "reservation", "2" => "ILL")),
              array("from" => "ExpectedDelivery", "to" => "orderExpectedAvailabilityDate", "date" => "swap"));
            foreach ($rsnr->getElementsByTagName("ReservationNotReady") as $r)
              $this->move_tags($r, $ord->orderNotReady[]->_value, $trans);

        // bookings
            $book = &$res->userStatus->_value->bookings->_value;
            $bookings = &$dom->getElementsByTagName("Bookings")->item(0);
            $trans = array(
              array("from" => "BookingID", "to" => "bookingId"),
              array("from" => "Title", "to" => "itemDisplayTitle"),
              array("from" => "NumberOrdered", "to" => "bookingTotalCount"),
              array("from" => "NumberRetained", "to" => "bookingFetchedCount"),
              array("from" => "NumberLoaned", "to" => "bookingLoanedCount"),
              array("from" => "NumberReturned", "to" => "bookingReturnedCount"),
              array("from" => "StartingDate", "to" => "bookingStartDate", "date" => "swap"),
              array("from" => "EndingDate", "to" => "bookingEndDate", "date" => "swap"),
              array("from" => "BookingStatus", "to" => "bookingStatus", 
                      "enum" => array("Manko" => "deficit", 
                                      "Afsluttet" => "closed", 
                                      "Fanget" => "retained", 
                                      "Restordre" => "back order", 
                                      "Udl책nt" => "on loan", 
                                      "Delvis afleveret" => "partly returned", 
                                      "Delvis udl책nt" => "partly on loan", 
                                      "Delvis fanget" => "partly retained", 
                                      "Aktiv" => "active", 
                                      "Registreret" => "registered")),
              array("from" => "BookingNote", "to" => "bookingNote"),
              array("from" => "ServiceCounter", "to" => "agencyCounter"));
            foreach ($bookings->getElementsByTagName("Booking") as $b)
              $this->move_tags($b, $book->booking[]->_value, $trans);

        // illOrders
            $illloans = &$dom->getElementsByTagName("ILLoans")->item(0);
            $ill = &$res->userStatus->_value->illOrders->_value;
            $trans = array(
              array("from" => "Title", "to" => "itemDisplayTitle"),
              array("from" => "NCIP-Author", "to" => "itemAuthor"),
              array("from" => "NCIP-Title", "to" => "itemTitle"),
              array("from" => "NCIP-PublicationDate", "to" => "itemPublicationYear"),
              array("from" => "NCIP-UnstructuredHoldingsData", "to" => "itemSerialPartTitle"),
              array("from" => "LoanLendingLibrary", "to" => "illProviderAgencyId"),
              array("from" => "ILLStatus", "to" => "illStatus", 
                      "enum" => array("Ny" => "new", 
                                      "L책ner ukendt" => "unknown user", 
                                      "L책ngiver ukendt" => "unknown provider agency", 
                                      "Oprettet" => "created", 
                                      "Bestilt" => "ordered", 
                                      "Kan ikke leveres" => "can not be delivered", 
                                      "Rykket" => "dunned", 
                                      "Modtaget" => "recieved", 
                                      "Udl책nt" => "on loan", 
                                      "횠nskes fornyet" => "renewal wanted", 
                                      "Forespurgt forny" => "renewal requested", 
                                      "Kan ikke fornys" => "can not be renewed", 
                                      "Fornyet" => "renewed", 
                                      "Afleveret" => "returned", 
                                      "Slettet" => "deleted", 
                                      "videregivet" => "passed on", 
                                      "Afbestilt" => "cancelled", 
                                      "Afsendt" => "sent", 
                                      "Forfalden" => "due", 
                                      "Annuller fornyelse" => "cancel renewal")),
              array("from" => "OrderDate", "to" => "illOrderDate", "date" => "swap"),
              array("from" => "ExpectedDelivery", "to" => "orderExpectedAvailabilityDate", "date" => "swap"),
              array("from" => "DisposalID", "to" => "orderId"),
              array("from" => "CreationDate", "to" => "orderDate", "date" => "swap"),
              array("from" => "LastUseDate", "to" => "orderLastInterestDate", "date" => "swap"));
            foreach ($illloans->getElementsByTagName("ILLoan") as $l)
              $this->move_tags($l, $ill->illOrder[]->_value, $trans);
          } else
            $res->userError->_value = "cannot decode answer";

        }
      } else {
        $res->userError->_value = "unknown agencyId";
      }
    }

    $ret->userStatusResponse->_value = $res;
    //var_dump($param); print_r($res); die();
    return $ret;
  }





  /** \brief Echos config-settings
   *
   */
  public function show_info() {
    echo "<pre>";
    echo "version   " . $this->config->get_value("version", "setup") . "<br/>";
    echo "</pre>";
    die();
  }


  /** \brief Move nodes and attributes from dom-object to object according to rules in $tags
   *
   * @param $from dom object
   * @param $to result object
   * @param $tags array of nodes to move
   *
   * @return - called as procedure
   */
  private function move_tags(&$from, &$to, &$tags) {
//echo "from: " . var_export($from, TRUE) . " <br/>\n";
//echo "to: " . var_export($to, TRUE) . " <br/>\n";
//echo "tags: " . var_export($tags, TRUE) . " <br/>\n";
    foreach ($tags as $tag) 
      foreach ($from->getElementsByTagName($tag["from"]) as $node) {
        if ($tag["from_attr"])
          $node_val = $node->getAttribute($tag["from_attr"]);
        else
          $node_val = $node->nodeValue;
//echo "nodeval: $node_val <br/>\n";
        if ($tag["bool"])
          $to->{$tag["to"]}[]->_value = ($node_val == $tag["bool"] ? "true" : "false");
        elseif ($tag["enum"] && isset($tag["enum"][$node_val]))
          $to->{$tag["to"]}[]->_value = $tag["enum"][$node_val];
        elseif ($tag["obligatory"])
          $to->{$tag["to"]}[]->_value = $node_val;
        elseif ($node_val <> NULL)
          if ($tag["date"] == "swap")
            $to->{$tag["to"]}[]->_value = $this->from_zruth_date($node_val);
          elseif ($tag["decimal"])
            $to->{$tag["to"]}[]->_value = $this->from_zruth_decimal($node_val);
          else
            $to->{$tag["to"]}[]->_value = $node_val;
      }
  }

 /** \brief
  *  return converts from nnn.nn to nnn,nn
  */
  private function to_zruth_decimal($dec) {
    return str_replace(".", ",", $dec);
  }

 /** \brief
  *  return converts from nnn,nn to nnn.nn
  */
  private function from_zruth_decimal($dec) {
    return str_replace(",", ".", $dec);
  }

 /** \brief
  *  return converts from YYYY-MM-DD to DD-MM-YYYY
  */
  private function to_zruth_date($date) {
    if (strlen($date) == 10 && substr($date, 4, 1) == "-" && substr($date, 7, 1) == "-")
      return substr($date, 8) . "-" .  substr($date, 5, 2) . "-" .  substr($date, 0, 4);
    else
      return $date;
  }

 /** \brief
  *  return converts from DD-MM-YYYY to YYYY-MM-DD
  */
  private function from_zruth_date($date) {
    if (strlen($date) == 10 && substr($date, 2, 1) == "-" && substr($date, 5, 1) == "-")
      return substr($date, 6) . "-" .  substr($date, 3, 2) . "-" .  substr($date, 0, 2);
    else
      return $date;
  }

 /** \brief
  *  return only digits, so something like DK-710100 returns 710100
  */
  private function strip_agency($id) {
    return preg_replace('/\D/', '', $id);
  }

 /** \brief
  *  return true for xs:boolean
  */
  private function xs_true($xs_bool) {
    return ($xs_bool == "1" || strtoupper($xs_bool) == "TRUE");
  }


}

/*
 * MAIN
 */

$ws=new openRuth();

$ws->handle_request();

?>
