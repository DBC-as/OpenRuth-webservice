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

//  protected $curl;

  public function __construct(){
    webServiceServer::__construct('openruth.ini');

//    if (!$timeout = $this->config->get_value("curl_timeout", "setup"))
//      $timeout = 20;
//    $this->curl = new curl();
//    $this->curl->set_option(CURLOPT_TIMEOUT, $timeout);
  }


/*
 * Agency Information
 * ==================
 *
 *   agencyCounters
 *   holdings
 */

  /** \brief 
   */
// more than one agencyCounter not supported yet
  function agencyCounters($param) { 
    if (!$this->aaa->has_right("openruth", 500))
      $res->agencyError->_value = "authentication_error";
    else {
      $targets = $this->config->get_value("ruth", "ztargets");
      if ($tgt = $targets[$param->agencyId->_value]) {
        $z = new z3950();
        $z->set_target($tgt["host"]);
        $z->set_database($tgt["database"]."-libraryid");
        $z->set_authentication($tgt["authentication"]);
        $z->set_syntax("xml");
        $z->set_element("default");
        $rpn = "@attrset 1.2.840.10003.3.1000.105.3 @attr 1=1 %s";
        $z->set_rpn(sprintf($rpn, $param->agencyId->_value));
        $hits = $z->z3950_search($tgt["timeout"]);
        if ($err = $z->get_errno()) {
          $res->userError->_value = "cannot reach local system - (" . $err . ")";
        } elseif (empty($hits))
          $res->userError->_value = "No counters found";
        else {
          $rec = $z->z3950_record();
          //print_r($rec);
          $dom = new DomDocument();
          $dom->preserveWhiteSpace = false;
          if ($dom->loadXML($rec)) {
            $bos = &$dom->getElementsByTagName("BorrowerStatus")->item(0);
            $trans = array(
              array("from" => "LibraryNo", "to" => "agencyId"),
              array("from" => "LibraryName", "to" => "agencyName"),
              array("from" => "ResActivePeriode", "to" => "orderActivePeriod"));
            $this->move_tags($bos, $res, $trans);
            //$sc = &$res->agencyCounterInfo->_value;
            $sc = &$res;
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

  /** \brief 
   */
  function holdings($param) { 
// URL_ITEMORDER_BESTIL
//   $search["syntax"]  = "xml";
//  $search["element"] = "B3";
//  $search["schema"]  = "1.2.840.10003.13.7.2";

    if (!$this->aaa->has_right("openruth", 500))
      $res->agencyError->_value = "authentication_error";
    else {
      $res->bookingError->_value = "not implemented yet";
/*
      $targets = $this->config->get_value("ruth", "ztargets");
      if ($tgt = $targets[$param->agencyId->_value]) {
        $z = new z3950();
        $z->set_target($tgt["host"]);
        //$z->set_database($tgt["database"]."-ophelia");
        $z->set_database($tgt["database"]."-titles");
        $z->set_authentication($tgt["authentication"]);
        $z->set_syntax("xml");
        $z->set_element("B3");
        //$z->set_schema("1.2.840.10003.13.7.2");
        //$rpn = "@attr 4=103 @attr BIB1 1=12 %s";
        //$z->set_rpn(sprintf($rpn, $param->itemId[0]->_value));
        $rpn = "@attrset 1.2.840.10003.3.1000.105.3 @and @attr 1=1 %s @attr 1=2 %s";
        $z->set_rpn(sprintf($rpn, $param->agencyId->_value, $param->itemId[0]->_value));
        $hits = $z->z3950_search($tgt["timeout"]);
echo "hits: " . $hits . "\n";
echo "err: " . $z->get_errno() . "\n";
        if ($err = $z->get_errno()) {
          $res->agencyError->_value = "cannot reach local system - (" . $err . ")";
        } elseif (empty($hits))
          $res->agencyError->_value = "No holdings found";
        else {
          $rec = $z->z3950_record();
echo "rec: " . $rec . "\n";
          $dom = new DomDocument();
          $dom->preserveWhiteSpace = false;
          if ($dom->loadXML($rec)) {
            $hs = &$dom->getElementsByTagName("BorrowerStatus")->item(0);
          } else
            $res->userError->_value = "cannot decode answer";
        }
      } else
        $res->userError->_value = "unknown agencyId";
*/
    }

    $ret->holdingsResponse->_value = $res;
    //var_dump($param); var_dump($res); die();
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

  /** \brief 
   */
  function bookingInfo($param) { 
    if (!$this->aaa->has_right("openruth", 500))
      $res->bookingError->_value = "authentication_error";
    else {
      $res->bookingError->_value = "not implemented yet";
    }

    $ret->bookingInfoResponse->_value = $res;
    //var_dump($param); var_dump($res); die();
    return $ret;
  }

  /** \brief 
   */
  function bookItem($param) { 
    if (!$this->aaa->has_right("openruth", 500))
      $res->bookingError->_value = "authentication_error";
    else {
      $res->bookingError->_value = "not implemented yet";
    }

    $ret->bookItemResponse->_value = $res;
    //var_dump($param); var_dump($res); die();
    return $ret;
  }

  /** \brief 
   */
  function updateBooking($param) { 
    if (!$this->aaa->has_right("openruth", 500))
      $res->bookingError->_value = "authentication_error";
    else {
      $res->bookingError->_value = "not implemented yet";
    }

    $ret->updateBookingResponse->_value = $res;
    //var_dump($param); var_dump($res); die();
    return $ret;
  }

  /** \brief 
   */
  function cancelBooking($param) { 
    if (!$this->aaa->has_right("openruth", 500))
      $res->bookingError->_value = "authentication_error";
    else {
      $res->bookingError->_value = "not implemented yet";
    }

    $ret->cancelBookingResponse->_value = $res;
    var_dump($param); var_dump($res); die();
    return $ret;
  }


/*
 * ordersAndRenewal 
 * ================
 *   cancelOrder (DeleteReservation) 
 *   orderItem (ReserveMaterials) 
 *   updateOrder (UpdateOrders) 
 */

  /** \brief 
   */
  function cancelOrder($param) { 
    if (!$this->aaa->has_right("openruth", 500))
      $res->cancelOrderError->_value = "authentication_error";
    else {
      $res->cancelOrderError->_value = "not implemented yet";
    }

    $ret->cancelOrderResponse->_value = $res;
    var_dump($param); var_dump($res); die();
    return $ret;
  }

  /** \brief 
   */
  function orderItem($param) { 
    if (!$this->aaa->has_right("openruth", 500))
      $res->orderItemError->_value = "authentication_error";
    else {
      $targets = $this->config->get_value("ruth", "ztargets");
      if ($tgt = $targets[$param->agencyId->_value]) {
    // build order
        $ord = &$order->Reservation->_value;
        $ord->LibraryNo->_value = $param->agencyId->_value;
        $ord->BorrowerTicketNo->_value = $param->userId->_value;
        $ord->DisposalNote->_value = $param->orderNote->_value;
        $ord->LastUseDate->_value = sprintf("%02d-%02d-%04d", 
                                            substr($param->orderLastInterestDate->_value, 8, 2), 
                                            substr($param->orderLastInterestDate->_value, 5, 2), 
                                            substr($param->orderLastInterestDate->_value, 0, 4));
        $ord->ServiceCounter->_value = $param->agencyCounter->_value;
        $ord->Override->_value = ($param->agencyCounter->_value == "TRUE" ? "Y" : "N");
        $ord->Priority->_value = $param->orderPriority->_value;
        // ?????? $ord->DisposalType->_value = $param->xxxx->_value;
        if (is_array($param->orderItemId))
          foreach ($param->orderItemId as $oid) {
            $ord->MRIDS->_value->MRID[]->_value->ID->_value = $oid->_value;
            $ord->MRIDS->_value->MRID[]->_value->TitlePartNo->_value = 0;
          }
        else {
          $ord->MRIDS->_value->MRID->_value->ID->_value = $param->orderItemId->_value;
          $ord->MRIDS->_value->MRID->_value->TitlePartNo->_value = 0;
        }
        // ??????? TitlePartNo together with ID ???????
        $xml = '<?xml version="1.0" encoding="UTF-8"?'.'>' . $this->objconvert->obj2xml($order);
        
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
              verbose::log(ERROR, "ES order errno: " . $err->getAttribute("Err") . 
                                  " error: " . $err->nodeValue);
              $res->orderItemError->_value = "unspecified error, order not possible";
            } else {
              // order at least partly ok 
              foreach ($dom->getElementsByTagName("MRID") as $mrid) {
                unset($ir);
                $ir->orderItemId->_value = $mrid->getAttribute("Id");
                //$ir->orderItemTitlePart->_value = $mrid->getAttribute("Tp");
                if ($mrid->nodeValue)
                  switch ($mrid->nodeValue) {
                    case "1001" : $ir->orderItemError->_value = "already on loan by user"; break;
                    case "1002" : $ir->orderItemError->_value = "already reserved by user"; break;
                    case "1003" : $ir->orderItemError->_value = "no copies available for reservation"; break;
                    case "1004" : $ir->orderItemError->_value = "ordering not allowed for this user"; break;
                    case "1005" : $ir->orderItemError->_value = "loan not allowed for this user category"; break;
                    case "1006" : $ir->orderItemError->_value = "loan not allowed, user too young"; break;
                    case "1007" : $ir->orderItemError->_value = "unspecified error, order not possible"; break;
                    case "1008" : $ir->orderItemError->_value = "system error"; break;
                    default     : $ir->orderItemError->_value = "unknown error: " . $mrid->nodeValue; break;
                  }
                else
                  $ir->orderItemOk->_value = "TRUE";
                $res->orderItem[]->_value = $ir;
              }
            }
          }
        } else {
          verbose::log(ERROR, "ES order z-errno: " . $z->get_error_string());
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
/*Array
(
    [host] => z3950.q1fjern.integrabib.dk
    [database] => q1fjern-ophelia
    [authentication] => ophelia/q1fjern/uka1Rivo
    [format] => k
    [formats] => Array
        (
            [k] => xml/f2o6locations
            [l] => xml/f2o6locations
            [v] => xml/f2bindo6
            [ll] => xml/f2o6locations
            [rss] => xml/rss
        )
    [start] => 1
    [step] => 10
    [timeout] => 30
    [xml] => 
<Reservation><LibraryNo>100450</LibraryNo><BorrowerTicketNo>222</BorrowerTicketNo><DisposalNote></DisposalNote><LastUseDate>23-09-2010</LastUseDate><ServiceCounter>DBCMedier</ServiceCounter><Override>N</Override><Priority>3</Priority><DisposalType>N</DisposalType><MRIDS><MRID><ID>24624471</ID><TitlePartNo>0</TitlePartNo></MRID></MRIDS></Reservation>

<Reservation><LibraryNo>100450</LibraryNo><BorrowerTicketNo>0019</BorrowerTicketNo><DisposalNote>This is an order note</DisposalNote><LastUseDate>25-01-2011</LastUseDate><ServiceCounter>DBCMedier</ServiceCounter><Override>N</Override><Priority>0</Priority><MRIDS><MRID><ID>1122334455</ID><TitlePartNo>0</TitlePartNo></MRID></MRIDS></Reservation>
    [xmlresult] => <ReservationResponse><BorrowerError>0</BorrowerError><MRID Id="24624471" Tp="0">1007</MRID></ReservationResponse>
/*

  /** \brief 
   */
  function updateOrder($param) { 
    if (!$this->aaa->has_right("openruth", 500))
      $res->updateOrderError->_value = "authentication_error";
    else {
      $res->updateOrderError->_value = "not implemented yet";
    }

    $ret->updateOrderResponse->_value = $res;
    //var_dump($param); var_dump($res); die();
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

  /** \brief 
   */
  function renewLoan($param) { 
    if (!$this->aaa->has_right("openruth", 500))
      $res->renewLoanError->_value = "authentication_error";
    else {
      $res->renewLoanError->_value = "not implemented yet";
    }

    $ret->renewLoanResponse->_value = $res;
    //var_dump($param); var_dump($res); die();
    return $ret;
  }

  /** \brief 
   */
  function updateUserInfo($param) { 
    if (!$this->aaa->has_right("openruth", 500))
      $res->userError->_value = "authentication_error";
    else {
      $res->userError->_value = "not implemented yet";
    }

    $ret->updateUserInfoResponse->_value = $res;
    //var_dump($param); var_dump($res); die();
    return $ret;
  }

  /** \brief 
   */
  function userCheck($param) { 
    if (!$this->aaa->has_right("openruth", 500))
      $res->agencyError->_value = "authentication_error";
    else {
      $targets = $this->config->get_value("ruth", "ztargets");
      if ($tgt = $targets[$param->agencyId->_value]) {
        $z = new z3950();
        $z->set_target($tgt["host"]);
        $z->set_database($tgt["database"]."-borrowercheck");
        $z->set_authentication($tgt["authentication"]);
        $z->set_syntax("xml");
        $z->set_element("test");
        $rpn = "@attrset 1.2.840.10003.3.1000.105.3 @and @attr 1=1 %s @and @attr 1=4 %s @attr 1=5 %s";
        $z->set_rpn(sprintf($rpn, $param->agencyId->_value, $param->userId->_value, $param->userPinCode->_value));
        $hits = $z->z3950_search($tgt["timeout"]);
//var_dump($hits);
//var_dump($z->get_errno());
        if ($err = $z->get_errno()) {
          $res->userError->_value = "cannot reach local system - (" . $err . ")";
        } elseif (empty($hits))
          $res->userError->_value = "unknown userId";
        else {
          $rec = $z->z3950_record();
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
            $this->move_tags($chk, $res, $trans);
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
   */
  function userPayment($param) { 
    if (!$this->aaa->has_right("openruth", 500))
      $res->userPaymentError->_value = "authentication_error";
    else {
      $res->userPaymentError->_value = "not implemented yet";
    }

    $ret->userPayResponse->_value = $res;
    //var_dump($param); var_dump($res); die();
    return $ret;
  }

  /** \brief 
   */
  function userStatus($param) { 
    if (!$this->aaa->has_right("openruth", 500))
      $res->userError->_value = "authentication_error";
    else {
      $targets = $this->config->get_value("ruth", "ztargets");
      if ($tgt = $targets[$param->agencyId->_value]) {
        $z = new z3950();
        $z->set_target($tgt["host"]);
        $z->set_database($tgt["database"]."-borrowerstatus");
        $z->set_authentication($tgt["authentication"]);
        $z->set_syntax("xml");
        $z->set_element("default");
        $rpn = "@attrset 1.2.840.10003.3.1000.105.3 @and @attr 1=1 %s @and @attr 1=4 %s @attr 1=5 %s";
        $z->set_rpn(sprintf($rpn, $param->agencyId->_value, $param->userId->_value, $param->userPinCode->_value));
        $hits = $z->z3950_search($tgt["timeout"]);
        if ($err = $z->get_errno()) {
          if ($err == 1103) $res->userError->_value = "unknown userId";
          elseif ($err == 1104) $res->userError->_value = "wrong pin code";
          else $res->userError->_value = "cannot reach local system - (" . $err . ")";
        } elseif (empty($hits))
          $res->userError->_value = "unknown userId";
        else {
          $rec = $z->z3950_record();
          //print_r($rec); die();
          $dom = new DomDocument();
          $dom->preserveWhiteSpace = false;
          if ($dom->loadXML($rec)) {
        // userInfo
            $loaner = &$dom->getElementsByTagName("Loaner")->item(0);
            $ui = &$res->userInfo->_value;
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
              array("from" => "Email", "to" => "userEmail"),
              array("from" => "StandardCounter", "to" => "agencyCounter"),
              array("from" => "ReservationAllowed", "to" => "userOrderAllowed", "bool" => "y"),
              array("from" => "BookingAllowed", "to" => "userBookingAllowed", "bool" => "y"),
              array("from" => "Penalties", "to" => "userFeesTotal"),
              array("from" => "MobilePhone", "to" => "userMobilePhone"),
              array("from" => "Journal", "to" => "userNote"),
              array("from" => "JournalDate", "to" => "userNoteDate", "date" => "swap"),
              array("from" => "JournalTxt", "to" => "userNoteTxt"));
            $this->move_tags($loaner, $ui, $trans);

        // fines
            $fi = &$res->fines->_value;
            $trans = array(
              array("from" => "ServiceDate", "to" => "fineDate", "date" => "swap"),
              array("from" => "ServiceCounter", "to" => "agencyCounter"),
              array("from" => "Title", "to" => "itemDisplayTitle"),
              array("from" => "Amount", "to" => "fineAmount"),
              array("from" => "Payed", "to" => "fineAmountPaid"),
              array("from" => "ServiceType", "to" => "fineType", "enum" => array("Late" => "late", "Recall1" => "first recall", "Recall2" => "second recall", "Recall3" => "third recall", "Compensation" => "compensation")),
              array("from" => "InvoiceNo", "to" => "fineInvoiceNumber"));
            foreach ($dom->getElementsByTagName("Fines") as $fines)
              foreach ($fines->getElementsByTagName("Fine") as $fine)
                $this->move_tags($fine, $fi->fine[]->_value, $trans);
            

        // loans
            $los = &$res->loans->_value;
            $loans = &$dom->getElementsByTagName("Loans")->item(0);
            $trans = array(
              array("from" => "RenewChecked", "to" => "renewAllLoansAllowed", "bool" => "y"));
            $this->move_tags($loans, $los, $trans);
            $status = &$dom->getElementsByTagName("Status");
            $trans = array(
              array("from" => "LoanCat", "to" => "loanCategory"),
              array("from" => "MatAtHome", "to" => "loanCategoryCount"));
            $trans_2 = array(
              array("from" => "RecallType", "to" => "loanRecallType"),
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
              array("from" => "Returndate", "to" => "loanReturnDate", "date" => "swap"),
              array("from" => "LastRenewal", "to" => "loanLastRenewedDate", "date" => "swap"),
              array("from" => "LoanStatus", "to" => "loanStatus"),
              array("from" => "RecallType", "to" => "loanRecallType"),
              array("from" => "RecallDate", "to" => "loanRecallDate", "date" => "swap"),
              array("from" => "CanRenew", "to" => "loanRenewable", "enum" => array("0" => "renewable", "1" => "not renewable", "2" => "ILL, renewable", "3" => "ILL, not renewable")));
            foreach ($loans->getElementsByTagName("Loan") as $loan)
              $this->move_tags($loan, $los->loan[]->_value, $trans);

        // orders
            $ord = &$res->orders->_value;
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
              array("from" => "DisposalId", "to" => "orderId"),
              array("from" => "CreationDate", "to" => "orderDate", "date" => "swap"),
              array("from" => "Arrived", "to" => "orderArrived", "bool" => "y"));
            foreach ($rsr->getElementsByTagName("ReservationReady") as $r)
              $this->move_tags($r, $ord->ordersReady[]->_value, $trans);
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
              array("from" => "DisposalId", "to" => "orderId"),
              array("from" => "CreationDate", "to" => "orderDate", "date" => "swap"),
              array("from" => "Arrived", "to" => "orderArrived", "bool" => "y"),
              array("from" => "LastUseDate", "to" => "orderLastInterstDate", "date" => "swap"),
              array("from" => "Priority", "to" => "orderPriority"),
              array("from" => "QueNumber", "to" => "orderQuePosition"),
              array("from" => "DisposalNote", "to" => "orderNote"),
              array("from" => "DisposalType", "to" => "orderType", "enum" => array("0" => "booking", "1" => "reservation", "2" => "ILL")));
            foreach ($rsnr->getElementsByTagName("ReservationNotReady") as $r)
              $this->move_tags($r, $ord->ordersNotReady[]->_value, $trans);

        // bookings
            $book = &$res->bookings->_value;
            $bookings = &$dom->getElementsByTagName("Bookings")->item(0);
            $trans = array(
              array("from" => "BookingId", "to" => "bookingId"),
              array("from" => "Title", "to" => "itemDisplayTitle"),
              array("from" => "NumberOrdered", "to" => "bookingTotalCount"),
              array("from" => "NumberLoaned", "to" => "bookingLoanedCount"),
              array("from" => "NumberReturned", "to" => "bookingReturnedCount"),
              array("from" => "StartingDate", "to" => "bookingStartDate", "date" => "swap"),
              array("from" => "EndingDate", "to" => "bookingEndDate", "date" => "swap"),
              array("from" => "BookingStatus", "to" => "bookingStatus", 
                      "enum" => array("Manko" => "deficit", 
                                      "Afsluttet" => "closed", 
                                      "Fanget" => "retained", 
                                      "Restordre" => "back order", 
                                      "Udl�nt" => "on loan", 
                                      "Delvis afleveret" => "partly returned", 
                                      "Delvis udl�nt" => "partly on loan", 
                                      "Delvis fanget" => "partly retained", 
                                      "Aktiv" => "active", 
                                      "Registreret" => "registered")),
              array("from" => "ServiceCounter", "to" => "agencyCounter"));
            foreach ($bookings->getElementsByTagName("Booking") as $b)
              $this->move_tags($b, $book->booking[]->_value, $trans);

        // illOrders
            $illloans = &$dom->getElementsByTagName("ILLoans")->item(0);
            $ill = &$res->illOrders->_value;
            $trans = array(
              array("from" => "Title", "to" => "itemDisplayTitle"),
              array("from" => "NCIP-Author", "to" => "itemAuthor"),
              array("from" => "NCIP-Title", "to" => "itemTitle"),
              array("from" => "NCIP-PublicationDate", "to" => "itemPublicationYear"),
              array("from" => "NCIP-UnstructuredHoldingsData", "to" => "itemSerialPartTitle"),
              array("from" => "LoanLendingLibrary", "to" => "illProviderAgencyId"),
              array("from" => "ILLStatus", "to" => "illStatus", 
                      "enum" => array("Ny" => "new", 
                                      "L�ner ukendt" => "unknown user", 
                                      "L�ngiver ukendt" => "unknown provider agency", 
                                      "Oprettet" => "created", 
                                      "Bestilt" => "ordered", 
                                      "Kan ikke leveres" => "can not be delivered", 
                                      "Rykket" => "dunned", 
                                      "Modtaget" => "recieved", 
                                      "Udl�nt" => "on loan", 
                                      "�nskes fornyet" => "renewal wanted", 
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
              array("from" => "OrderDate", "to" => "illOrderDate"),
              array("from" => "ExpectedDelivery", "to" => "orderExpectedAvailabilityDate"),
              array("from" => "DisposalId", "to" => "orderId"),
              array("from" => "CreationDate", "to" => "orderDate"),
              array("from" => "LastUseDate", "to" => "orderLastInterestDate"));
            foreach ($illloans->getElementsByTagName("ILLoan") as $l)
              $this->move_tags($l, $ord->illOrder[]->_value, $trans);
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


  private function move_tags(&$from, &$to, &$tags) {
    foreach ($tags as $tag) 
      foreach ($from->getElementsByTagName($tag["from"]) as $node) {
        $node_val = $node->nodeValue;
        if ($tag["bool"])
          $to->{$tag["to"]}[]->_value = ($node_val == $tag["bool"] ? "TRUE" : "FALSE");
        elseif ($tag["enum"] && isset($tag["enum"][$node_val]))
          $to->{$tag["to"]}[]->_value = $tag["enum"][$node_val];
        elseif ($tag["obligatory"])
          $to->{$tag["to"]}[]->_value = $node_val;
        elseif ($node_val)
          if ($tag["date"] == "swap")
            $to->{$tag["to"]}[]->_value = substr($node_val, 6) . "-" . 
                                          substr($node_val, 3, 2) . "-" . 
                                          substr($node_val, 0, 2);
          else
            $to->{$tag["to"]}[]->_value = $node_val;
      }
  }

}

/*
 * MAIN
 */

$ws=new openRuth();

$ws->handle_request();

?>
