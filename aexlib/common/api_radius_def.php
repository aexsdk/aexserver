<?php
/*
 * Created on 2009_4-11
 *
 * To change the template for this generated file go to
 * Window - Preferences - PHPeclipse - PHP - Code Templates
 */

define ( 'RADIUS_ST_REGISTER', 230 );
define ( 'RADIUS_ST_INVITE', 231 );
define ( 'RADIUS_ST_SELECTROUTER', 232 );
define ( 'RADIUS_ST_QUERY_BALANCE', 235 );
define ( 'RADIUS_ST_RECHARGE', 236 );
define ( 'RADIUS_ST_BIND_CLI', 237 );
define ( 'RADIUS_ST_GETACCOUNT', 239 );

define ( 'NAS_ASTERISK', 'ASTERISK' );
define ( 'NAS_OPENSER', 'OPENSER' );
define ( 'NAS_ONDO_SIP', 'ONDO_SIP' );
define ( 'NAS_CALLBACK', 'CALLBACK' );

define ( 'Error_Reason', 112 );

define ( 'VENDOR_CISCO', 9 );

define ( 'Cisco_AVPair', 1 );
define ( 'Cisco_NAS_Port', 2 );
//
//  T.37 Store-and_Forward attributes.
//
define ( 'Cisco_Fax_Account_Id_Origin', 3 );
define ( 'Cisco_Fax_Msg_Id', 4 );
define ( 'Cisco_Fax_Pages', 5 );
define ( 'Cisco_Fax_Coverpage_Flag', 6 );
define ( 'Cisco_Fax_Modem_Time', 7 );
define ( 'Cisco_Fax_Connect_Speed', 8 );
define ( 'Cisco_Fax_Recipient_Count', 9 );
define ( 'Cisco_Fax_Process_Abort_Flag', 10 );
define ( 'Cisco_Fax_Dsn_Address', 11 );
define ( 'Cisco_Fax_Dsn_Flag', 12 );
define ( 'Cisco_Fax_Mdn_Address', 13 );
define ( 'Cisco_Fax_Mdn_Flag', 14 );
define ( 'Cisco_Fax_Auth_Status', 15 );
define ( 'Cisco_Email_Server_Address', 16 );
define ( 'Cisco_Email_Server_Ack_Flag', 17 );
define ( 'Cisco_Gateway_Id', 18 );
define ( 'Cisco_Call_Type', 19 );
define ( 'Cisco_Port_Used', 20 );
define ( 'Cisco_Abort_Cause', 21 );

//
//  Voice over IP attributes.
//
define ( 'h323_remote_address', 23 );
define ( 'h323_conf_id', 24 );
define ( 'h323_setup_time', 25 );
define ( 'h323_call_origin', 26 );
define ( 'h323_call_type', 27 );
define ( 'h323_connect_time', 28 );
define ( 'h323_disconnect_time', 29 );
define ( 'h323_disconnect_cause', 30 );
define ( 'h323_voice_quality', 31 );
define ( 'h323_gw_id', 33 );
define ( 'h323_incoming_conf_id', 35 );

define ( 'h323_credit_amount', 101 );
define ( 'h323_credit_time', 102 );
define ( 'h323_return_code', 103 );
define ( 'h323_prompt_id', 104 );
define ( 'h323_time_and_day', 105 );
define ( 'h323_redirect_number', 106 );
define ( 'h323_preferred_lang', 107 );
define ( 'h323_redirect_ip_address', 108 );
define ( 'h323_billing_model', 109 );
define ( 'h323_currency', 110 );
define ( 'subscriber', 111 );
define ( 'gw_rxd_cdn', 112 );
define ( 'gw_final_xlated_cdn', 113 );

// SIP Attributes
define ( 'call_id', 141 );
define ( 'session_protocol', 142 );
define ( 'method', 143 );
define ( 'prev_hop_via', 144 );
define ( 'prev_hop_ip', 145 );
define ( 'incoming_req_uri', 146 );
define ( 'outgoing_req_uri', 147 );
define ( 'next_hop_ip', 148 );
define ( 'next_hop_dn', 149 );
define ( 'sip_hdr', 150 );

//
//	Extra attributes sent by the Cisco, if you configure
//	"radius_server vsa accounting" (requires IOS11.2+).
//
define ( "Cisco_Multilink_ID", "187" );
define ( "Cisco_Num_In_Multilink", "188" );
define ( "Cisco_Pre_Input_Octets", "190" );
define ( "Cisco_Pre_Output_Octets", "191" );
define ( "Cisco_Pre_Input_Packets", "192" );
define ( "Cisco_Pre_Output_Packets", "193" );
define ( "Cisco_Maximum_Time", "194" );
define ( "Cisco_Disconnect_Cause", "195" );
define ( "Cisco_Data_Rate", "197" );
define ( "Cisco_PreSession_Time", "198" );
define ( "Cisco_PW_Lifetime", "208" );
define ( "Cisco_IP_Direct", "209" );
define ( "Cisco_PPP_VJ_Slot_Comp", "210" );
define ( "Cisco_PPP_Async_Map", " 212" );
define ( "Cisco_IP_Pool_Definition", "217" );
define ( "Cisco_Assign_IP_Pool", "218" );
define ( "Cisco_Route_IP", "228" );
define ( "Cisco_Link_Compression", "233" );
define ( "Cisco_Target_Util", "234" );
define ( "Cisco_Maximum_Channels", "235" );
define ( "Cisco_Data_Filter", "242" );
define ( "Cisco_Call_Filter", "243" );
define ( "Cisco_Idle_Limit", " 244" );
define ( "Cisco_Account_Info", "250" );
define ( "Cisco_Service_Info", "251" );
define ( "Cisco_Command_Code", "252" );
define ( "Cisco_Control_Info", "253" );
define ( "Cisco_Xmit_Rate", "255" );

define ( "Sip_Uri_User", '' );
define ( "SER_Uri_User", '' );
define ( "SER_RESPONSE_TIMESTAMP", 237 );
define ( "SER_REQUEST_TIMESTAMP", 232 );
define ( "SIP_IP_SOURCE_IP_ADDRESS", "" );
define ( "SIP_TRANSLATED_REQUEST_ID", "" );
define ( "SIP_FROM_TAG", "" );
define ( "SIP_TO_TAG", "" );
define ( "SIP_AVP", "" );

?>
