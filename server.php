<?php
/**
 *
 * This file is part of Open Library System.
 * Copyright © 2009, Dansk Bibliotekscenter a/s,
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
  function agencyCounters($param) { 
    if (!$this->aaa->has_right("openruth", 500))
      $res->agencyError->_value = "authentication_error";
    else {
    }

    $ret->agencyCountersResponse->_value = $res;
    var_dump($param); var_dump($res); die();
    return $ret;
  }

  /** \brief 
   */
  function holdings($param) { 
    if (!$this->aaa->has_right("openruth", 500))
      $res->agencyError->_value = "authentication_error";
    else {
    }

    $ret->holdingsResponse->_value = $res;
    var_dump($param); var_dump($res); die();
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
      $res->agencyError->_value = "authentication_error";
    else {
    }

    $ret->bookingInfoResponse->_value = $res;
    var_dump($param); var_dump($res); die();
    return $ret;
  }

  /** \brief 
   */
  function bookItem($param) { 
    if (!$this->aaa->has_right("openruth", 500))
      $res->agencyError->_value = "authentication_error";
    else {
    }

    $ret->bookItemResponse->_value = $res;
    var_dump($param); var_dump($res); die();
    return $ret;
  }

  /** \brief 
   */
  function updateBooking($param) { 
    if (!$this->aaa->has_right("openruth", 500))
      $res->agencyError->_value = "authentication_error";
    else {
    }

    $ret->updateBookingResponse->_value = $res;
    var_dump($param); var_dump($res); die();
    return $ret;
  }

  /** \brief 
   */
  function cancelBooking($param) { 
    if (!$this->aaa->has_right("openruth", 500))
      $res->agencyError->_value = "authentication_error";
    else {
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
      $res->agencyError->_value = "authentication_error";
    else {
    }

    $ret->cancelOrderResponse->_value = $res;
    var_dump($param); var_dump($res); die();
    return $ret;
  }

  /** \brief 
   */
  function orderItem($param) { 
    if (!$this->aaa->has_right("openruth", 500))
      $res->agencyError->_value = "authentication_error";
    else {
    }

    $ret->orderItemResponse->_value = $res;
    var_dump($param); var_dump($res); die();
    return $ret;
  }

  /** \brief 
   */
  function updateOrder($param) { 
    if (!$this->aaa->has_right("openruth", 500))
      $res->agencyError->_value = "authentication_error";
    else {
    }

    $ret->updateOrderResponse->_value = $res;
    var_dump($param); var_dump($res); die();
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
      $res->agencyError->_value = "authentication_error";
    else {
    }

    $ret->renewLoanResponse->_value = $res;
    var_dump($param); var_dump($res); die();
    return $ret;
  }

  /** \brief 
   */
  function updateUserInfo($param) { 
    if (!$this->aaa->has_right("openruth", 500))
      $res->agencyError->_value = "authentication_error";
    else {
    }

    $ret->updateUserInfoResponse->_value = $res;
    var_dump($param); var_dump($res); die();
    return $ret;
  }

  /** \brief 
   */
  function userCheck($param) { 
    if (!$this->aaa->has_right("openruth", 500))
      $res->agencyError->_value = "authentication_error";
    else {
    }

    $ret->userCheckResponse->_value = $res;
    var_dump($param); var_dump($res); die();
    return $ret;
  }

  /** \brief 
   */
  function userPayment($param) { 
    if (!$this->aaa->has_right("openruth", 500))
      $res->agencyError->_value = "authentication_error";
    else {
    }

    $ret->userPayResponse->_value = $res;
    var_dump($param); var_dump($res); die();
    return $ret;
  }

  /** \brief 
   */
  function userStatus($param) { 
    if (!$this->aaa->has_right("openruth", 500))
      $res->userError->_value = "authentication_error";
    else {
      $targets = $this->config->get_value("ruth_borrowerstatus", "ztargets");
      if ($tgt = $targets[$param->agencyId->_value]) {
        $z = new z3950();
        $z->set_target($tgt["host"]);
        $z->set_authentication($tgt["authentication"]);
        $z->set_syntax("xml");
        $z->set_element("default");
        $z->set_rpn(sprintf($tgt["rpn"], $param->agencyId->_value, $param->userId->_value, $param->userPinCode->_value));
        $hits = $z->z3950_search($tgt["timeout"]);
        if ($err = $z->get_errno()) {
          if ($err == 1103) $res->userError->_value = "unknown userId";
          elseif ($err == 1104) $res->userError->_value = "wrong pin code";
          else $res->userError->_value = "cannot reach local system - (" . $err . ")";
        } elseif (empty($hits))
          $res->userError->_value = "unknown userId";
        else {
          $rec = $z->z3950_record();
          //print_r($rec);
          $dom = new DomDocument();
          $dom->preserveWhiteSpace = false;
          if ($dom->loadXML($rec)) {
        // userInfo
            $loaner = &$dom->getElementsByTagName("Loaner")->item(0);
            $ui = &$res->userInfo->_value;
            $this->move_tags($loaner, $ui, 
                             array("FirstName" => "userFirstName",
                                   "LastName" => "userLastName",
                                   "Category" => "userCategoryName",
                                   "Address" => "userAddress",
                                   "PostCode" => "userPostCode",
                                   "PostDistrict" => "userCity",
                                   "Village" => "userVillage",
                                   "Telephone" => "userTelephone",
                                   "AttName" => "userAttName",
                                   "CoName" => "userCoName",
                                   "Pincode" => "userPinCode",
                                   "Email" => "userEmail",
                                   "StandardCounter" => "agencyCounter",
                                   "ReservationAllowed" => "userOrderAllowed",
                                   "BookingAllowed" => "userBookingAllowed",
                                   "Penalties" => "userFeesTotal",
                                   "MobilePhone" => "userMobilePhone",
                                   "Journal" => "userNote",
                                   "JournalDate" => "userNoteDate",
                                   "JournalTxt" => "userNoteTxt"));

        // fines
            $fi = &$res->fines->_value;
            foreach ($dom->getElementsByTagName("Fines") as $fines)
              foreach ($fines->getElementsByTagName("Fine") as $fine)
                $this->move_tags($fine, $fi->fine[]->_value, 
                                 array("ServiceDate" => "fineDate",
                                       "ServiceCounter" => "agencyCounter",
                                       "Title" => "itemDisplayTitle",
                                       "Amount" => "fineAmount",
                                       "Payed" => "fineAmountPaid",
                                       "ServiceType" => "fineType",
                                       "InvoiceNo" => "fineInvoiceNumber"));
            

        // loans
            $los = &$res->loans->_value;
            $loans = &$dom->getElementsByTagName("Loans")->item(0);
            $this->move_tags($loans, $los, array("RenewChecked" => "renewAllLoansAllowed"));
            $status = &$dom->getElementsByTagName("Status");
            foreach ($dom->getElementsByTagName("Status") as $status) {
              foreach ($status->getElementsByTagName("CategoryLoans") as $c_los)
                $this->move_tags($c_los, $los->loanCategories[]->_value, 
                                 array("LoanCat" => "loanCategory",
                                       "MatAtHome" => "loanCategoryCount"));
              foreach ($status->getElementsByTagName("RecallStatus") as $r_stat)
                $this->move_tags($r_stat, $los->loanRecallTypes[]->_value, 
                                 array("RecallType" => "loanRecallType",
                                       "Number" => "loanRecallTypeCount "));
            }
            foreach ($loans->getElementsByTagName("Loan") as $loan)
              $this->move_tags($loan, $los->loan[]->_value, 
                               array("Title" => "itemDisplayTitle",
                                     "NCIP-Author" => "itemAuthor",
                                     "NCIP-Title" => "itemTitle",
                                     "NCIP-PublicationDate" => "itemPublicationYear",
                                     "CopyNo" => "copyId",
                                     "LoanDate" => "loanDate",
                                     "Returndate" => "loanReturnDate",
                                     "LastRenewal" => "loanLastRenewedDate",
                                     "LoanStatus" => "loanStatus",
                                     "RecallType" => "loanRecallType",
                                     "RecallDate" => "loanRecallDate",
                                     "CanRenew" => "loanRenewable"));

        // orders
            $reservations = &$dom->getElementsByTagName("Reservations")->item(0);
            $ord = &$res->orders->_value;
            foreach ($reservations->getElementsByTagName("ReservationsReady") as $r)
              $this->move_tags($r, $ord->ordersReady[]->_value, 
                               array("Title" => "itemDisplayTitle",
                                     "NCIP-Author" => "itemAuthor",
                                     "NCIP-Title" => "itemTitle",
                                     "NCIP-PublicationDate" => "itemPublicationYear",
                                     "NCIP-UnstructuredHoldingsData" => "itemSerialPartTitle",
                                     "ServiceCounter" => "agencyCounter",
                                     "CollectDate" => "orderPickUpDate",
                                     "RetainedDate" => "orderFetchedDate",
                                     "CollectNo" => "orderPickUpId",
                                     "DisposalId" => "orderId",
                                     "CreationDate" => "orderDate",
                                     "Arrived" => "orderArrived"));
            foreach ($reservations->getElementsByTagName("ReservationsNotReady") as $r)
              $this->move_tags($r, $ord->ordersNotReady[]->_value, 
                               array("Title" => "itemDisplayTitle",
                                     "NCIP-Author" => "itemAuthor",
                                     "NCIP-Title" => "itemTitle",
                                     "NCIP-PublicationDate" => "itemPublicationYear",
                                     "NCIP-UnstructuredHoldingsData" => "itemSerialPartTitle",
                                     "ServiceCounter" => "agencyCounter",
                                     "CollectDate" => "orderPickUpDate",
                                     "RetainedDate" => "orderFetchedDate",
                                     "CollectNo" => "orderPickUpId",
                                     "DisposalId" => "orderId",
                                     "CreationDate" => "orderDate",
                                     "Arrived" => "orderArrived",
                                     "LastUseDate" => "orderLastInterstDate",
                                     "Priority" => "orderPriority",
                                     "QueNumber" => "orderQuePosition",
                                     "DisposalNote" => "orderNote",
                                     "DisposalType" => "orderType"));

        // bookings
            $res->bookings->_value = "";

        // illOrders
            $res->illOrders->_value = "";
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


  private function move_tags(&$from, &$to, $tags) {
    foreach ($tags as $from_tag => $to_tag) 
      foreach ($from->getElementsByTagName($from_tag) as $node)
        if ($content = $node->nodeValue)
          $to->{$to_tag}[]->_value = $content;
  }
}

/*
 * MAIN
 */

$ws=new openRuth();

$ws->handle_request();

?>

