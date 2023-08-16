<?php

define("CHEETAH_API_BASE_API_URL_STAGING", ""); // 
define("CHEETAH_API_BASE_LINK_URL_STAGING", ""); // 
define("HUBSPOT_API_KEY", ""); // 
define("HUBSPOT_API_BASE_API_URL", ""); // 
define("HUBSPOT_API_TOKEN_KEY_SANDBOX", ""); // 
define("HUBSPOT_ACCOUNT_ID_SANDBOX", ""); // 
define("CHEETAH_API_BASE_API_URL", ""); // 
define("CHEETAH_API_BASE_LINK_URL", "");
define("HUBSPOT_API_TOKEN_KEY", ""); // 
define("HUBSPOT_ACCOUNT_ID_LIVE", ""); // 
define("NOVA_API_KEY", ""); // 
define("NOVA_API_URL", ""); // 
define("CHEETAH_API_KEY", ""); // 
define("CHEETAH_API_USERNAME", ""); // 
define("CHEETAH_API_PASSWORD", ""); // 
define("DB_HOST", ""); // 
define("DB_DATABASE", ""); // 
define("DB_USER", ""); // 
define("DB_PASSWORD", ""); // 

function exception_error_handler($severity, $message, $file, $line)
{
	if (!(error_reporting() & $severity)) {

		return;
	}
	throw new ErrorException($message, 0, $severity, $file, $line);
}
set_error_handler("exception_error_handler");

ini_set("memory_limit", "2048M");
date_default_timezone_set("UTC");

use daraeman\NovaApi;
use daraeman\HubspotApi;
use daraeman\CheetahApi;
use daraeman\Zapier;

require_once(__DIR__ . "/../../env.php");
require_once(__DIR__ . "/env.php");
require_once(__DIR__ . "/class/NovaApi.php");
require_once(__DIR__ . "/class/HubspotApi.php");
require_once(__DIR__ . "/class/CheetahApi.php");
require_once(__DIR__ . "/../phpmailer/functions.php");
require_once(__DIR__ . "/../../class/DB.php");
require_once(__DIR__ . "/../../class/Zapier.php");

$is_sandbox = FALSE;
$disable_ip = FALSE;
if ($is_sandbox) {
	$cheetah_api_url = CHEETAH_API_BASE_API_URL_STAGING;
	$cheetah_link = CHEETAH_API_BASE_LINK_URL_STAGING;

	$hubspot_api_key = HUBSPOT_API_KEY;
	$hubspot_api_base_key = HUBSPOT_API_BASE_API_URL;
	$hubsport_token_key = HUBSPOT_API_TOKEN_KEY_SANDBOX;
	$hubsport_account_id = HUBSPOT_ACCOUNT_ID_SANDBOX;
} else {
	$cheetah_api_url = CHEETAH_API_BASE_API_URL;
	$cheetah_link = CHEETAH_API_BASE_LINK_URL;

	$hubspot_api_key = HUBSPOT_API_KEY;
	$hubspot_api_base_key = HUBSPOT_API_BASE_API_URL;
	$hubsport_token_key = HUBSPOT_API_TOKEN_KEY;
	$hubsport_account_id = HUBSPOT_ACCOUNT_ID_LIVE;
}

$info_log = ($is_sandbox) ? __DIR__ . "/logs/new_nova_webhook." . date("Y_m_d") . ".sandbox.info.log" : __DIR__ . "/logs/nova_webhook." . date("Y_m_d") . ".info.log";
$error_log = ($is_sandbox) ? __DIR__ . "/logs/new_nova_webhook." . date("Y_m_d") . ".sandbox.error.log" : __DIR__ . "/logs/nova_webhook." . date("Y_m_d") . ".error.log";
$deburg_log = ($is_sandbox) ? __DIR__ . "/logs/new_nova_webhook." . date("Y_m_d") . ".sandbox.deburg.log" : __DIR__ . "/logs/nova_webhook." . date("Y_m_d") . ".deburg_log.log";

try {

	$info_file = "";

	if (empty($argv[2])) {

		if (empty($argv[1])) {
			throw new Error("Missing info file argument");
		}

		$info_file = $argv[1];

		logInfo("info file", $info_file);

		$json = file_get_contents($info_file);
		logInfo("json", $json);

		if (empty($json)) {
			logError("empty json");
			exit(1);
		}

		$data = json_decode($json, TRUE);
		logInfo("data", print_r($data, TRUE));

		if (empty($data)) {
			logError("empty data");
			exit(1);
		}

		if (empty($data["transaction"])) {
			logError("empty transaction");
			exit(1);
		}

		if (empty($data["transaction"]["id"])) {
			logError("empty transaction id");
			exit(1);
		}

		$transaction_id = $data["transaction"]["id"];
		$transaction_status = $data["metadata"]["targetStateKey"];
	}

	if (
		$transaction_status !== "submitted"
	) {
		logInfo("Ignoring this transaction status [" . $transaction_status . "]");
		deburg("not submitted--> ", $transaction_id);
	}

	if (
		$transaction_status !== "submitted"
		&& $transaction_status !== "pendingSubmission"
	) {
		logInfo("Ignoring this transaction status [" . $transaction_status . "]");
		deburg("not submitted or pending--> ", $transaction_id);
	} else {
		deburg("submitted --> ", $transaction_id);
		$nova_api = new NovaApi([
			"api_key" => NOVA_API_KEY,
			"api_base_url" => NOVA_API_URL,
		]);

		$hubspot_api = new HubspotApi([
			"api_key" => $hubspot_api_key,
			"api_base_url" => $hubspot_api_base_key,
			"tokenKey" => $hubsport_token_key,
			"hub_account_id" => $hubsport_account_id,
		]);

		$cheetah_api = new CheetahApi([
			"api_key" => CHEETAH_API_KEY,
			"username" => CHEETAH_API_USERNAME,
			"password" => CHEETAH_API_PASSWORD,
			"api_base_url" => $cheetah_api_url,
			"db_mysql" => new DB([
				"host" => DB_HOST,
				"database" => DB_DATABASE,
				"user" => DB_USER,
				"password" => DB_PASSWORD,
				"debug" => FALSE,
			]),
		]);

		deburg("Transaction ID--> ", $transaction_id);
		$transaction = $nova_api->getTransaction(
			$transaction_id,
			'{
				transactionItems {
					data
					vulcanKey
				}
				templateVersion {
					templateId
				}
				transactionRecipients {
					activatedAt
					entityId
					noticeName
					noticeEmail
					vulcanKey
					entity {
						createdAt
						email
						id
						name
						phone
						settings
						type
					}		
				}
			}'

		);

		if ($transaction["error"]) {
			logError("Failed to fetch nova transaction nova_transaction_id[" . $transaction_id . "]", $transaction, TRUE);
			deburg("Failed to fetch nova transaction--> ", $transaction_id);
		} else {
			deburg("transaction data--> ", $transaction);
		}

		$transaction = json_decode($transaction["content"], TRUE);
		$change_formate_for_all_template = [];
		foreach ($transaction["data"]["transaction"]["transactionItems"] as $transaction_item) {
			if ($transaction_item["vulcanKey"] === "funding") {
				if (!empty($transaction_item['data'])) {
					$change_formate_for_all_template[0] = $transaction_item;
				}
			}
			if ($transaction_item["vulcanKey"] === "confirm") {
				if (!empty($transaction_item['data'])) {
					$change_formate_for_all_template[1] = $transaction_item;
				}
			}
			if ($transaction_item["vulcanKey"] === "entityInformation") {
				if (!empty($transaction_item['data'])) {
					$change_formate_for_all_template[2] = $transaction_item;
				}
			}
			if ($transaction_item["vulcanKey"] === "benes") {
				if (!empty($transaction_item['data'])) {
					$change_formate_for_all_template[3] = $transaction_item;
				}
			}
			if ($transaction_item["vulcanKey"] === "fees") {
				if (!empty($transaction_item['data'])) {
					$change_formate_for_all_template[4] = $transaction_item;
				}
			}
		}
		ksort($change_formate_for_all_template);
		$transaction["data"]["transaction"]['transactionItems'] = $change_formate_for_all_template;

		if ($transaction["data"]["transaction"]["templateVersion"]["templateId"] == 'a460d677-89db-4d95-bd79-1a0b4ab98e1c' || $transaction["data"]["transaction"]["templateVersion"]["templateId"] == 'f3126e08-7784-4ec1-b498-f49e5cf8cdbf') {
			$transaction["data"]["transaction"]['transactionItems'][2]['data']['dob'] = $transaction["data"]["transaction"]['transactionItems'][2]['data']['minorDob'];
			$transaction["data"]["transaction"]['transactionItems'][2]['data']['ssn'] = $transaction["data"]["transaction"]['transactionItems'][2]['data']['minorSsn'];
			$transaction["data"]["transaction"]['transactionItems'][2]['data']['lastName'] = $transaction["data"]["transaction"]['transactionItems'][2]['data']['minorLastName'];
			$transaction["data"]["transaction"]['transactionItems'][2]['data']['firstName'] = $transaction["data"]["transaction"]['transactionItems'][2]['data']['minorFirstName'];
		}

		if ($transaction["data"]["transaction"]["templateVersion"]["templateId"] == "ae4646f8-8620-42ea-ad2d-8e57b3b415ef") {
			$transaction["data"]["transaction"]['transactionItems'][2]['data']['legalAddressRegion'] = $transaction["data"]["transaction"]['transactionItems'][2]['data']['mailingAccountStateSuccesor'];
			$transaction["data"]["transaction"]['transactionItems'][2]['data']['legalAddressPostalCode'] = $transaction["data"]["transaction"]['transactionItems'][2]['data']['mailingAccountZipSuccesor'];
			$transaction["data"]["transaction"]['transactionItems'][2]['data']['legalAddressStreet'] = $transaction["data"]["transaction"]['transactionItems'][2]['data']['mailingAccountAddressSuccesor'];
			$transaction["data"]["transaction"]['transactionItems'][2]['data']['legalAddressCountry'] = $transaction["data"]["transaction"]['transactionItems'][2]['data']['mailingAccountCountrySuccesor'];
			$transaction["data"]["transaction"]['transactionItems'][2]['data']['legalAddressCity'] = $transaction["data"]["transaction"]['transactionItems'][2]['data']['mailingAccountPlanSuccesor'];
			$transaction["data"]["transaction"]['transactionItems'][2]['data']['prEmail'] = $transaction["data"]["transaction"]['transactionItems'][2]['data']['email'];
			$transaction["data"]["transaction"]['transactionItems'][2]['data']['dob'] = $transaction["data"]["transaction"]['transactionItems'][2]['data']['dob401'];
		}
		if ($transaction["data"]["transaction"]["templateVersion"]["templateId"] == "c483fbb5-e4d5-4361-900f-a039680bade3" || $transaction["data"]["transaction"]["templateVersion"]["templateId"] == "c523298e-ed43-49f0-9e59-a1a771cf7a40" || $transaction["data"]["transaction"]["templateVersion"]["templateId"] == "efef3fa8-5999-4dab-8328-b24eb0c7e2cc" || $transaction["data"]["transaction"]["templateVersion"]["templateId"] == "b6b92888-ad5f-496e-9693-ff34f48a5941" || $transaction["data"]["transaction"]["templateVersion"]["templateId"] == "fc6d76e8-3819-4f1b-9501-cb24529448cc") {
			$transaction["data"]["transaction"]["transactionItems"][4]['data']['accountFeesOption'] = 'invoice';
		}

		if (!isset($transaction["data"]["transaction"]["transactionItems"][4]['vulcanKey'])) {
			$transaction["data"]["transaction"]["transactionItems"][4]['vulcanKey'] = 'fees';
		}

		if (isset($transaction['data']['transaction']['transactionItems'][2]['data']['legalAddressCountry']) && $transaction['data']['transaction']['transactionItems'][2]['data']['legalAddressCountry'] == 'PR') {

			$transaction['data']['transaction']['transactionItems'][2]['data']['legalAddressCountry'] = 'PuertoRico';

			if (isset($transaction['data']['transaction']['transactionItems'][2]['data']['legalAddressRegion']) && $transaction['data']['transaction']['transactionItems'][2]['data']['legalAddressRegion'] == 'notApplicable') {

				$transaction['data']['transaction']['transactionItems'][2]['data']['legalAddressRegion'] = '';
			}
		}

		logInfo("transaction", json_encode($transaction, JSON_PRETTY_PRINT));

		if ($transaction_status === "pendingSubmission") {

			if (empty($transaction["data"]) || empty($transaction["data"]["transaction"]["transactionRecipients"])) {
				logError("Nova transaction missing data transactionRecipients nova_transaction_id[" . $transaction_id . "]", $transaction, TRUE);
			}

			$main_transaction_info = $transaction["data"]["transaction"]["transactionRecipients"][0]["entity"];

			if (empty($main_transaction_info["ssn"])) {
				logError("Nova transaction missing ssn nova_transaction_id[" . $transaction_id . "]", $transaction, TRUE);
			}

			$hubspot_contacts = $hubspot_api->searchContacts(
				[
					"filterGroups" => [
						[
							"filters" => [
								[
									"value" => str_replace(' ', '', $main_transaction_info["prEmail"]),
									"propertyName" => "email",
									"operator" => "EQ"
								],

							]
						]
					]
				]
			);

			logInfo("hubspot_contacts results ", $hubspot_contacts);

			if (empty($hubspot_contacts["error"]) && !isset($hubspot_contacts["error"])) {
				$hubspot_contacts = array_values($hubspot_contacts);
				if (!empty($hubspot_contacts)) {
					$hubspot_contact_id = $hubspot_contacts[0]["id"];
				}
			} else {
				$hubspot_contacts = [];
				deburg("Failed to Search Contact in hub--> ", $transaction_id);
			}

			logInfo("transaction pending flow");

			$hubspot_contact_params = [
				"firstname" => $main_transaction_info["firstName"],
				"lastname" => $main_transaction_info["lastName"],
				"cheetah_n_a" => $cheetah_link . "/Individuals/Detail/" . ((!empty($cheetah_match_na["IdentityRecordId"])) ? $cheetah_match_na["IdentityRecordId"] : ""),
				"address" => (!empty($main_transaction_info["legalAddressStreet"])) ? $main_transaction_info["legalAddressStreet"] : "",
				"city" => (!empty($main_transaction_info["legalAddressCity"])) ? $main_transaction_info["legalAddressCity"] : "",
				"zip" => (!empty($main_transaction_info["legalAddressPostalCode"])) ? $main_transaction_info["legalAddressPostalCode"] : "",
				"state" => (!empty($main_transaction_info["legalAddressRegion"])) ? $main_transaction_info["legalAddressRegion"] : "",
				"country" => (!empty($main_transaction_info["legalAddressCountry"])) ? $main_transaction_info["legalAddressCountry"] : "",
				"phone" => (!empty($main_transaction_info["primaryPhone"])) ? $main_transaction_info["primaryPhone"] : "",
				"email" => (!empty($main_transaction_info["prEmail"])) ? $main_transaction_info["prEmail"] : "",
				"cheetahidentityrecordid" => (!empty($cheetah_match_na["IdentityRecordId"])) ? $cheetah_match_na["IdentityRecordId"] : "",
				"birthdate" => date("Y-m-d\T00:00:00.000\Z", strtotime($main_transaction_info["dob"])),
				"lifecyclestage" => 56257461,
			];

			logInfo("hubspot_contact_params", $hubspot_contact_params);

			if (empty($hubspot_contacts)) {

				$response = $hubspot_api->createContact(
					$hubspot_contact_params
				);

				if (!empty($response["error"])) {
					logError("Failed to create Hubspot Contact", [
						"response" => $response,
						"hubspot_contact_params" => $hubspot_contact_params,
					], TRUE);
					deburg("Failed to Create Contact in hub 1--> ", $transaction_id);
				}

				$hubspot_contact_id = $response["content"]["id"];
			} else {

				logInfo("update hubspot contact");

				$hubspot_contact_id = $hubspot_contacts[0]["id"];

				$response = $hubspot_api->updateContact(
					$hubspot_contact_id,
					$hubspot_contact_params
				);

				if (!empty($response["error"])) {
					logError("Failed to update Hubspot Contact", [
						"response" => $response,
						"hubspot_contact_params" => $hubspot_contact_params,
					], TRUE);
					deburg("Failed to update Contact in hub 1--> ", $transaction_id);
				}
			}
		} else if ($transaction_status === "submitted") {

			$main_transaction_info = FALSE;
			foreach ($transaction["data"]["transaction"]["transactionItems"] as $transaction_item) {
				if ($transaction_item["vulcanKey"] === "entityInformation") {
					$main_transaction_info = $transaction_item["data"];
				}
			}

			if ($main_transaction_info === FALSE) {
				logError("Failed to parse nova transaction nova_transaction_id[" . $transaction_id . "]", $transaction, TRUE);
			}

			if (empty($main_transaction_info["ssn"])) {
				logError("Nova transaction missing ssn nova_transaction_id[" . $transaction_id . "]", $transaction, TRUE);
			}

			$name_and_addresses = $cheetah_api->getNameAndAddresses(
				FALSE,
				$main_transaction_info["ssn"]
			);

			if ($name_and_addresses["error"]) {
				logError("Failed to fetch Cheetah Name and Addresses nova_transaction_id[" . $transaction_id . "]", $name_and_addresses, TRUE);
				deburg("Failed to get name and address 1--> ", $transaction_id);
			}

			$name_and_addresses = $name_and_addresses["content"];

			logInfo("name_and_addresses", $name_and_addresses);

			$cheetah_match_na = FALSE;
			foreach ($name_and_addresses as $cheetah_contact) {

				if (
					$cheetah_contact["TaxIdType"] === "USSocialSecurityNumber"
					&& $cheetah_contact["TaxId"] === $main_transaction_info["ssn"]
				) {
					$cheetah_match_na = $cheetah_contact;
				}
			}

			if ($transaction_status === "submitted") {

				if ($cheetah_match_na === FALSE) {
					logInfo("Did not match Cheetah Name and Address ssn [" . $main_transaction_info["ssn"] . "], assuming does not exist");

					$name_address_params = $cheetah_api->getNameAndAddressesParamsFromNova(
						FALSE,
						$main_transaction_info
					);

					logInfo("name_address_params >>>>>>>>>>>>>>>>>> Failed to create Cheetah Name and Address", json_encode($name_address_params, JSON_PRETTY_PRINT));

					$create_response = $cheetah_api->createNameAndAddress(
						$name_address_params
					);

					if (!empty($create_response["error"])) {
						logError("Failed to create Cheetah Name and Address", $create_response, TRUE);
						deburg("Failed to create cheetah name and address--> ", $transaction_id);
					} else {
						logInfo("Cheetah Name and Address created [" . $create_response["content"]["IdentityRecordId"] . "]");
						$cheetah_match_na = $create_response["content"];
						logInfo("cheetah_match_na", $cheetah_match_na);
					}
				} else {

					logInfo("cheetah_match_na", $cheetah_match_na);

					$name_address_params = $cheetah_api->getNameAndAddressesParamsFromNova(
						$cheetah_match_na["IdentityRecordId"],
						$main_transaction_info,
						$cheetah_match_na
					);

					$name_address_params['TaxIdType'] = 'USSocialSecurityNumber';
					$name_address_params['TaxIdStatusType'] = 'Known';

					$update_response = $cheetah_api->updateNameAndAddress(
						$cheetah_match_na["IdentityRecordId"],
						$name_address_params
					);

					if (!empty($update_response["error"])) {
						logError("Failed to update Cheetah Name and Address [" . $cheetah_match_na["IdentityRecordId"] . "]", $update_response, TRUE);
						deburg("Failed to update cheetah name and address--> ", $transaction_id);
					} else {
						logInfo("Cheetah Name and Address updated [" . $update_response["content"]["IdentityRecordId"] . "]");
					}
				}
			}

			$hubspot_contacts = $hubspot_api->searchContacts(
				[
					"filterGroups" => [
						[
							"filters" => [
								[
									"value" => str_replace(' ', '', $main_transaction_info["prEmail"]),
									"propertyName" => "email",
									"operator" => "EQ"
								],

							]
						]
					]
				]
			);
			logInfo("hubspot_contacts results ", $hubspot_contacts);

			if (empty($hubspot_contacts["error"]) && !isset($hubspot_contacts["error"])) {
				$hubspot_contacts = array_values($hubspot_contacts);
				if (!empty($hubspot_contacts)) {
					$hubspot_contact_id = $hubspot_contacts[0]["id"];
				}
			} else {
				$hubspot_contacts = [];
			}

			if ($transaction_status === "submitted") {

				logInfo("transaction pending flow");

				logInfo("transaction submitted flow");

				logInfo("main_transaction_info", $main_transaction_info);

				$interested_party_NA_id = FALSE;
				if (
					!empty($main_transaction_info["ipFirstName"])
					&& !$disable_ip
				) {

					logInfo("interested party na");

					$name_and_addresses_ip = $cheetah_api->getNameAndAddresses(
						FALSE,
						$main_transaction_info["ipSsn"]
					);

					if ($name_and_addresses_ip["error"]) {
						logError("Failed to fetch Interested Party Cheetah Name and Addresses nova_transaction_id[" . $transaction_id . "]", $name_and_addresses_ip, TRUE);
						deburg("Failed to get N&A Cheetah 2--> ", $transaction_id);
					}

					$name_and_addresses_ip = $name_and_addresses_ip["content"];

					logInfo("name_and_addresses", $name_and_addresses_ip);

					$cheetah_match_ip = FALSE;
					foreach ($name_and_addresses_ip as $cheetah_contact_ip) {

						if (
							$cheetah_contact_ip["TaxIdType"] === "USSocialSecurityNumber"
							&& $cheetah_contact_ip["TaxId"] === $main_transaction_info["ipSsn"]
						) {
							$cheetah_match_ip = $cheetah_contact_ip;
						}
					}

					if ($cheetah_match_ip === FALSE) {
						logInfo("Did not match Interested Party Cheetah Name and Address ipSsn [" . $main_transaction_info["ipSsn"] . "], assuming does not exist");

						$name_address_params_ip = $cheetah_api->getInterestedPartyNameAndAddressesParamsFromNova(
							$main_transaction_info
						);

						$create_response = $cheetah_api->createNameAndAddress(
							$name_address_params_ip
						);

						if (!empty($create_response["error"])) {
							logError("Failed to create Interested Party Cheetah Name and Address", $create_response, TRUE);
							deburg("Failed to Create cheetah N&A 2--> ", $transaction_id);
						}

						$interested_party_NA_id = $create_response["content"]["IdentityRecordId"];
					} else {
						logInfo("matched interested party na ipSsn [" . $main_transaction_info["ipSsn"] . "]");
						$interested_party_NA_id = $cheetah_match_ip["IdentityRecordId"];
					}

					logInfo("interested_party_NA_id [" . $interested_party_NA_id . "]");
				}

				$create_account_params = $cheetah_api->getCreateAccountParamsFromNova(
					$transaction["data"]["transaction"]
				);

				logInfo("create_account_params", $create_account_params);

				logInfo(" name_and_addresses  identity ID >>> " . json_encode($name_and_addresses, JSON_PRETTY_PRINT));

				$response = $cheetah_api->createAccount(
					$create_account_params
				);

				if (!empty($response["error"])) {
					logError("Failed to create Cheetah Account", [
						"response" => $response,
						"account_params" => $create_account_params,
					], TRUE);
					deburg("Failed to Create cheetah account--> 1", $transaction_id);
				}

				$account = $response["content"];
				$account_id = $account["AccountId"];
				$account_number = $account["AccountNumber"];

				if (empty($response["error"])) {
					$get_created_account = $cheetah_api->getAccount(
						$account_id
					);
					$update_created_account_settings = $get_created_account["content"];

					$nova_fees_option = $transaction["data"]["transaction"]["transactionItems"][4]['data']['accountFeesOption'];

					$account_db = $cheetah_api->getAccountDB(
						$transaction["data"]["transaction"]["templateVersion"]["templateId"]
					);
					$is_crypto_type = (strpos(strtolower($account_db["type"]), "crypto") !== FALSE);
					$newDate = date('Y-m-d\T18:00:00.000+0000', strtotime('+ 11 months'));
					$date = new DateTime($newDate);
					$date->modify("last day of this month");

					$DateTaxYearEnd = date("Y-12-31\T18:00:00.000+0000");

					$update_created_account_settings['AccountSettings']['IsPurchaseRestricted'] =  FALSE;
					$update_created_account_settings['AccountSettings']['IsSalesRestricted'] =  FALSE;
					$update_created_account_settings['AccountSettings']['IsPrincipalOnly'] =  TRUE;
					$update_created_account_settings['AccountSettings']['ReinvestOptionType'] =  "SystemDefault";
					$update_created_account_settings['AccountSettings']['AmortizationType'] =  "None";
					$update_created_account_settings['AccountSettings']['IsNetCashOverdraft'] =  TRUE;
					$update_created_account_settings['AccountSettings']['AdministrativeReviewFrequencyMonthOffset'] =  (date("n") === "1") ? 12 : ((int) date("n") - 1); 
					$update_created_account_settings['AccountSettings']['InvestmentPowerTypeId'] =  3;
					$update_created_account_settings['AccountSettings']['ProxyInvestmentPowerType'] =  'NoneNOBO';
					$update_created_account_settings['AccountSettings']['DateTaxYearEnd'] =  $DateTaxYearEnd;
					$update_created_account_settings['AccountSettings']['Tax1099LevelType'] =  'NotA1099TypeAccount';
					$update_created_account_settings['AccountSettings']['Tax1099RecipientType'] =  'BeneficiaryRecipient';
					$update_created_account_settings['AccountSettings']['TaxLotHarvestingType'] =  'Average';
					$update_created_account_settings['AccountSettings']['PrincipalReserveAmount'] =  ($is_crypto_type || $nova_fees_option === "cc") ? 0 : 0;

					$update_account_settings = $cheetah_api->updateAccount(
						$account_id,
						$update_created_account_settings
					);

					if (!empty($update_account_settings["error"])) {
						logError("Failed to update Account settings", [
							"account_id" => $account_id,
							"response" => $update_account_settings,
						], TRUE);
					}

					logInfo("update_account_Settings success", $update_account_settings);

				}

				logInfo("account_id------->", $account_id);
				logInfo("account_number------->", $account_number);

				$account_relationships = [
					[
						"AccountId" => $account_id,
						"AccountRelationshipId" => 0,
						"IdentityRecordId" => $cheetah_match_na["IdentityRecordId"],
						"AccountRelationshipTypeId" => 6,
						"OwnershipPercent" => 1.00,
						"TrusteePercent" => 0,
						"isProxyRecipient" => FALSE,
						"DoesReceiveApprovalLetter" => TRUE,
						"DoesReceiveTradeNotification" => TRUE,
						"DoesUseAccunet" => TRUE,
					],
					[
						"IdentityRecordId" => 4,
						"AccountId" => $account_id,
						"AccountRelationshipId" => 0,
						"AccountRelationshipTypeId" => 5,
						"AccountRelationshipTypeName" => "Other Manager",
						"OwnershipPercent" => 0.00,
						"isProxyRecipient" => FALSE,
						"DoesReceiveApprovalLetter" => FALSE,
						"DoesUseAccunet" => FALSE,
						"DomainModelClass" => "Individual",
						"DomainModelClassName" => "Individual",
						"isActive" => TRUE,
						"IdentityClassificationType" => "Officer",
						"IdentityClassificationTypeName" => "Officer",
					],
					[
						"IdentityRecordId" => 6,
						"AccountId" => $account_id,
						"AccountRelationshipId" => 0,
						"AccountRelationshipTypeId" => 2,
						"AccountRelationshipTypeName" => "Account Administrator",
						"OwnershipPercent" => 0.00,
						"isProxyRecipient" => FALSE,
						"DoesReceiveApprovalLetter" => FALSE,
						"DoesUseAccunet" => FALSE,
						"DomainModelClass" => "Individual",
						"DomainModelClassName" => "Individual",
						"isActive" => TRUE,
						"IdentityClassificationType" => "Officer",
						"IdentityClassificationTypeName" => "Officer",
					],
				];

				if ($transaction["data"]["transaction"]["templateVersion"]["templateId"] == "efef3fa8-5999-4dab-8328-b24eb0c7e2cc" || $transaction["data"]["transaction"]["templateVersion"]["templateId"] == "b6b92888-ad5f-496e-9693-ff34f48a5941" || $transaction["data"]["transaction"]["templateVersion"]["templateId"] == "fc6d76e8-3819-4f1b-9501-cb24529448cc") {
					$interested_party_NA_id = 18435;
					$main_transaction_info["ipBool"] = 1;
					$main_transaction_info["ipFirstName"] = 'Broad Street';
					$main_transaction_info["ipLastName"] = 'Global Fund, LLC';
				}

				if ($interested_party_NA_id !== FALSE) {

					$account_relationships[] = [
						"AccountId" => $account_id,
						"AccountRelationshipId" => 0,
						"IdentityRecordId" => $interested_party_NA_id,
						"AccountRelationshipTypeId" => ($main_transaction_info["ipBool"]) ? 39 : 33,
						"AccountRelationshipTypeName" => $main_transaction_info["ipFirstName"] . " " . $main_transaction_info["ipLastName"],
						"OwnershipPercent" => 0.00,
						"TrusteePercent" => 0,
						"isProxyRecipient" => FALSE,
						"DoesReceiveApprovalLetter" => TRUE,
						"DoesReceiveTradeNotification" => TRUE,
						"DoesUseAccunet" => ($main_transaction_info["ipBool"]) ? TRUE : FALSE,
						"IdentityClassificationType" => "Informational"
					];
				}

				logInfo("account_relationships", $account_relationships);

				foreach ($account_relationships as $account_relationship) {

					$response = $cheetah_api->addAccountRelationship($account_relationship);

					logInfo("addAccountRelationship response", $response);

					if (!empty($response["error"])) {
						logError("Failed to create Cheetah Account Relationship", [
							"response" => $response,
							"account_relationship" => $account_relationship,
						], TRUE);
						deburg("Failed to Create cheetah account relationship--> ", $transaction_id);
					}
				}

				$existing_fee_settings = $cheetah_api->getAccountFeeSettings2(
					$account_id
				);

				if (!empty($existing_fee_settings["error"])) {
					logError("Failed to get Cheetah Fee Settings", [
						"response" => $existing_fee_settings,
						"account_id" => $account_id,
					], TRUE);
					deburg("Failed to get cheetah account settings--> ", $transaction_id);
				}

				logInfo("existing_fee_settings", $existing_fee_settings);

				$account_db = $cheetah_api->getAccountDB(
					$transaction["data"]["transaction"]["templateVersion"]["templateId"]
				);

				logInfo("templateId", $transaction["data"]["transaction"]["templateVersion"]["templateId"]);
				logInfo("account_db", $account_db);

				if (empty($account_db)) {
					logError("Failed to get hubspot account details from database", FALSE, TRUE);
				}

				$nova_fees = FALSE;
				foreach ($transaction["data"]["transaction"]["transactionItems"] as $transaction_item) {
					if ($transaction_item["vulcanKey"] === "fees") {
						$nova_fees = $transaction_item["data"];
					}
				}

				$is_crypto = (strpos(strtolower($account_db["type"]), "crypto") !== FALSE);

				$fee_setting = $existing_fee_settings["content"][0];

				$params = $fee_setting;

				$hubspot_contact_params = [
					"firstname" => $main_transaction_info["firstName"],
					"lastname" => $main_transaction_info["lastName"],
					"cheetah_n_a" => $cheetah_link . "/Individuals/Detail/" . ((!empty($cheetah_match_na["IdentityRecordId"])) ? $cheetah_match_na["IdentityRecordId"] : ""),
					"address" => (!empty($main_transaction_info["legalAddressStreet"])) ? $main_transaction_info["legalAddressStreet"] : "",
					"city" => (!empty($main_transaction_info["legalAddressCity"])) ? $main_transaction_info["legalAddressCity"] : "",
					"zip" => (!empty($main_transaction_info["legalAddressPostalCode"])) ? $main_transaction_info["legalAddressPostalCode"] : "",
					"state" => (!empty($main_transaction_info["legalAddressRegion"])) ? $main_transaction_info["legalAddressRegion"] : "",
					"country" => (!empty($main_transaction_info["legalAddressCountry"])) ? $main_transaction_info["legalAddressCountry"] : "",
					"phone" => (!empty($main_transaction_info["primaryPhone"])) ? $main_transaction_info["primaryPhone"] : "",
					"email" => (!empty($main_transaction_info["prEmail"])) ? $main_transaction_info["prEmail"] : "",
					"cheetahidentityrecordid" => (!empty($cheetah_match_na["IdentityRecordId"])) ? $cheetah_match_na["IdentityRecordId"] : "",
					"birthdate" => date("Y-m-d\T00:00:00.000\Z", strtotime($main_transaction_info["dob"])),
					"lifecyclestage" => 56257461,
				];

				if (empty($hubspot_contacts)) {

					$response = $hubspot_api->createContact(
						$hubspot_contact_params
					);

					if (!empty($response["error"])) {
						logError("Failed to create Hubspot Contact", [
							"response" => $response,
							"hubspot_contact_params" => $hubspot_contact_params,
						], TRUE);
						deburg("Failed to create Contact in hub 2--> ", $transaction_id);
					}

					$hubspot_contact_id = $response["content"]["id"];
				} else {

					logInfo("using hubspot contact and update", $hubspot_contacts[0]);

					$hubspot_contact_id = $hubspot_contacts[0]["id"];

					$response = $hubspot_api->updateContact(
						$hubspot_contact_id,
						$hubspot_contact_params
					);

					if (!empty($response["error"])) {
						logError("Failed to update Hubspot Contact", [
							"response" => $response,
							"hubspot_contact_params" => $hubspot_contact_params,
						], TRUE);
						deburg("Failed to upadate Contact in hub 2--> ", $transaction_id);
					}
				}

				$financial_account_params = [
					"account_category" => $account_db["account_category"],
					"financialaccount_name" => $cheetah_api->getDisplayName(
						$account_db,
						$main_transaction_info
					),
					"account_number" => $account_number,
					"cheetah_link" => $cheetah_link . "/Accounts/Detail/" . $account_id,
					"date_opened" => date("Y-m-d\T00:00:00.000\Z"),
					"division" => $account_db["division"],
					"status" => "Open",
				];
				if (isset($main_transaction_info["promo"]) && isset($main_transaction_info["referral"])) {
					$financial_account_params['promo_code'] = $main_transaction_info["promo"];
					$financial_account_params['other_referral_relationship'] = $main_transaction_info["referral"];
				}
				logInfo("createFinancialObject params", $financial_account_params);

				$response = $hubspot_api->createFinancialObject($financial_account_params);

				logInfo("createFinancialObject response", $response);

				if (
					!empty($response["error"])
					&& $response["headers"][0] !== "HTTP/1.1 201 Created"
				) {
					logError("Failed to create Hubspot Financial Object", [
						"response" => $response,
						"financial_account_params" => $financial_account_params,
					], TRUE);
					deburg("Failed to Create Financial Object--> ", $transaction_id);
				}

				$hubspot_financial_object_id = $response["content"]["id"];

				if (empty($hubspot_contact_id)) {
					logError("Missing Hubspot hubspot_contact_id", [
						"response" => $response,
					], TRUE);
				}

				$response = $hubspot_api->createAssociation(
					[
						"associationCategory" => "USER_DEFINED",
						"associationTypeId" => 24,
					],
					[
						"type" => "contact",
						"id" => $hubspot_contact_id,
					],
					[
						"type" => "p21293392_financialaccount",
						"id" => $hubspot_financial_object_id,
					],
				);

				if (!empty($response["error"])) {
					logError("Failed to create Hubspot Contact -> Financial Object Association", [
						"response" => $response,
						"type" => "USER_DEFINED",
						"type_id" => 24,
						"hubspot_contact_id" => $hubspot_contact_id,
						"hubspot_financial_object_id" => "hubspot_financial_object_id",
					], TRUE);
					deburg("Failed to Create Contact and financial object association--> ", $transaction_id);
				}

				if ($main_transaction_info) {

					$zap_url = "https://hooks.zapier.com/hooks/catch/10735686/ba6d5re/";
					$has_ip = $main_transaction_info["ipBool"];

					if ($transaction["data"]["transaction"]["templateVersion"]["templateId"] == "efef3fa8-5999-4dab-8328-b24eb0c7e2cc" || $transaction["data"]["transaction"]["templateVersion"]["templateId"] == "b6b92888-ad5f-496e-9693-ff34f48a5941" || $transaction["data"]["transaction"]["templateVersion"]["templateId"] == "fc6d76e8-3819-4f1b-9501-cb24529448cc") {

						$main_transaction_info["ipAddressStreet"] = '40 W Broad St, Ste 360';
						$main_transaction_info["ipAddressCity"] = 'Greenville';
						$main_transaction_info["ipAddressRegion"] = 'SC';
						$main_transaction_info["ipAddressPostalCode"] = 29601;
						$main_transaction_info["ipPrimaryPhone"] = '833-274-3863';
						$main_transaction_info["ipEmailAddress"] = 'info@bsgfund.com';
					}

					$zap_params = [
						"nova_id" => $transaction["data"]["transaction"]["templateVersion"]["templateId"],
						"beneficiary" => "true",
						"form1_first_name" => $main_transaction_info["firstName"],
						"form1_middle_name" => "",
						"form1_last_name" => $main_transaction_info["lastName"],
						"form1_date_of_birth" => substr($main_transaction_info["dob"], 0, 10),
						"form1_gender" => ucwords($main_transaction_info["gender"]),
						"form1_social_security_number" => substr($main_transaction_info["ssn"], -4),
						"form1_citizenship" => ($main_transaction_info["citizenship"] === "us") ? "United States" : $main_transaction_info["citizenship"],
						"form1_physical_address" => $main_transaction_info["legalAddressStreet"],
						"form1_street" => "",
						"form1_city" => $main_transaction_info["legalAddressCity"],
						"form1_state" => $main_transaction_info["legalAddressRegion"],
						"form1_zip" => $main_transaction_info["legalAddressPostalCode"],
						"form1_primary_phone_number" => $main_transaction_info["primaryPhone"],
						"form1_email" => $main_transaction_info["prEmail"],
						"form3_interested_party" => ($has_ip) ? "Yes" : "No",
						"form3_interested_first_name" => ($has_ip) ? $main_transaction_info["ipFirstName"] : "",
						"form3_interested_middle_name" => "",
						"form3_interested_last_name" => ($has_ip) ? $main_transaction_info["ipLastName"] : "",
						"form3_interested_physical_address" => ($has_ip) ? $main_transaction_info["ipAddressStreet"] : "",
						"form3_interested_street" => "",
						"form3_interested_city" => ($has_ip) ? $main_transaction_info["ipAddressCity"] : "",
						"form3_interested_state" => ($has_ip) ? $main_transaction_info["ipAddressRegion"] : "",
						"form3_interested_zip" => ($has_ip) ? $main_transaction_info["ipAddressPostalCode"] : "",
						"form3_primary_phone_number" => ($has_ip) ? $main_transaction_info["ipPrimaryPhone"] : "",
						"form3_email" => ($has_ip) ? $main_transaction_info["ipEmailAddress"] : "",
						"how_would_you_like_account_fees_to_be_paid" => ($nova_fees["accountFeesOption"] === "cc") ? "Charge my Credit/Debit Card" : "Deduct From Account",
						"Informational" => "true",
						"account_number" => $account_number,
						"account_category" => $account_db["account_category"],
						"cheetah_account_id" => $account_id,
					];

					logInfo("zap_url", $zap_url);
					logInfo("zap_params", $zap_params);

					$zap_response = Zapier::send(
						$zap_url,
						$zap_params
					);

					logInfo("zap_response", $zap_response);
				}

				if ($transaction_status === "submitted" && !empty($main_transaction_info["ipFirstName"]) && isset($main_transaction_info["copyIP"]) && !empty($main_transaction_info["copyIP"])) {

					$intrested_party_contacts = $hubspot_api->searchContacts(
						[
							"filterGroups" => [
								[
									"filters" => [
										[
											"value" => $main_transaction_info["ipEmailAddress"],
											"propertyName" => "email",
											"operator" => "EQ"
										]
									]
								]
							]
						]
					);

					logInfo("intrested_party_contacts results ", $intrested_party_contacts);

					if (empty($intrested_party_contacts["error"]) && !isset($intrested_party_contacts["error"])) {
						$intrested_party_contacts = array_values($intrested_party_contacts);
						if (!empty($intrested_party_contacts)) {
							$hubspot_contact_id = $intrested_party_contacts[0]["id"];
						}
					} else {
						$intrested_party_contacts = [];
						deburg("Failed to Search Intresred Party Contact in hub--> ", $transaction_id);
					}

					$ip_hubspot_contact_params = [
						"firstname" => $main_transaction_info["ipFirstName"],
						"lastname" => $main_transaction_info["ipLastName"],
						"cheetah_n_a" => "",
						"address" => (!empty($main_transaction_info["ipAddressStreet"])) ? $main_transaction_info["ipAddressStreet"] : "",
						"city" => (!empty($main_transaction_info["ipAddressCity"])) ? $main_transaction_info["ipAddressCity"] : "",
						"zip" => (!empty($main_transaction_info["ipAddressPostalCode"])) ? $main_transaction_info["ipAddressPostalCode"] : "",
						"state" => (!empty($main_transaction_info["ipAddressRegion"])) ? $main_transaction_info["ipAddressRegion"] : "",
						"country" => (!empty($main_transaction_info["ipAddressCountry"])) ? $main_transaction_info["ipAddressCountry"] : "",
						"phone" => (!empty($main_transaction_info["ipPrimaryPhone"])) ? $main_transaction_info["ipPrimaryPhone"] : "",
						"email" => (!empty($main_transaction_info["ipEmailAddress"])) ? $main_transaction_info["ipEmailAddress"] : "",
						"cheetahidentityrecordid" =>  "",
						"birthdate" => date("Y-m-d\T00:00:00.000\Z", strtotime($main_transaction_info["ipDob"])),
						"lifecyclestage" => 56257461,
					];

					logInfo("ip_hubspot_contact_params", $ip_hubspot_contact_params);

					if (empty($intrested_party_contacts)) {
						$response = $hubspot_api->createContact(
							$ip_hubspot_contact_params
						);

						if (!empty($response["error"])) {
							logError("Failed to create Intrested Party Hubspot Contact", [
								"response" => $response,
								"ip_hubspot_contact_params" => $ip_hubspot_contact_params,
							], TRUE);
						}

						$ip_hubspot_contact_id = $response["content"]["id"];
					} else {
						logInfo("update hubspot Ip contact");

						$ip_hubspot_contact_id = $intrested_party_contacts[0]["id"];

						$response = $hubspot_api->updateContact(
							$ip_hubspot_contact_id,
							$ip_hubspot_contact_params
						);

						if (!empty($response["error"])) {
							logError("Failed to update Hubspot IP Contact", [
								"response" => $response,
								"ip_hubspot_contact_params" => $ip_hubspot_contact_params,
							], TRUE);
						}
					}

					if ($ip_hubspot_contact_id) {
						$response = $hubspot_api->createAssociation(
							[
								"associationCategory" => "USER_DEFINED",
								"associationTypeId" =>  24 
							],
							[
								"type" => "contacts",
								"id" => $ip_hubspot_contact_id,
							],
							[
								"type" => "p" . $hubsport_account_id . "_financialaccount",
								"id" => $hubspot_financial_object_id,
							]
						);

						if (!empty($response["error"])) {
							logError("Failed to create Hubspot IP Contact -> Financial Object Association", [
								"response" => $response,
								"type" => "USER_DEFINED",
								"type_id" => 24,
								"ip_hubspot_contact_id" => $ip_hubspot_contact_id,
								"hubspot_financial_object_id" => $hubspot_financial_object_id,
							], TRUE);
						}

						$response = $hubspot_api->setAssociationLables(
							[
								"associationCategory" => "USER_DEFINED",
								"associationTypeId" =>  42, 
							],
							$ip_hubspot_contact_id,
							"p" . $hubsport_account_id . "_financialaccount",
							$hubspot_financial_object_id
						);

						echo "<pre>";
						print_r($response);
						echo "</pre>";

					}
				}
				deburg("End--------------------------------- ", '------------------------');
			} else {
				logError("unknown transaction flow [" . $transaction_status . "]");
			}
		}
	}

	if (!empty($info_file)) {
		rename($info_file, __DIR__ . "/../../webhooks/nova/processed/" . basename($info_file));
	}

	logInfo("Success");
} catch (Throwable $error) {

	logError("Nova Test Outer Error [" . $error->getMessage() . "]", $error);
}

function logError(
	$message,
	$obj = "DSAFGSDTYTHRDFSFGTERfsdDSAFGSDTYTHRDFSFGTERfsd",
	$throw_error = FALSE
) {

	global $error_log,
		$errors,
		$is_sandbox;

	$errors[] = $message;

	if ($obj !== "DSAFGSDTYTHRDFSFGTERfsdDSAFGSDTYTHRDFSFGTERfsd") {
		$message .= "\n" . print_r($obj, TRUE);
	}

	echo $message;

	file_put_contents($error_log, "[" . date("Y-m-d H:i:s") . "] " . $message . "\n", FILE_APPEND);

	if ($throw_error) {
		throw new Error($message);
	}
}

function logInfo(
	$message,
	$obj = "DSAFGSDTYTHRDFSFGTERfsdDSAFGSDTYTHRDFSFGTERfsd"
) {

	global $info_log,
		$is_sandbox;

	if ($obj !== "DSAFGSDTYTHRDFSFGTERfsdDSAFGSDTYTHRDFSFGTERfsd") {
		$message .= "\n" . print_r($obj, TRUE);
	}

	echo $message . "\n";

	@file_put_contents($info_log, "[" . date("Y-m-d H:i:s") . "] " . $message . "\n", FILE_APPEND);
}

function deburg(
	$message,
	$obj = "DSAFGSDTYTHRDFSFGTERfsdDSAFGSDTYTHRDFSFGTERfsd"
) {

	global $deburg_log,
		$is_sandbox;

	if ($obj !== "DSAFGSDTYTHRDFSFGTERfsdDSAFGSDTYTHRDFSFGTERfsd") {
		$message .= "\n" . print_r($obj, TRUE);
	}

	echo $message . "\n";

	@file_put_contents($deburg_log, "[" . date("Y-m-d H:i:s") . "] " . $message . "\n", FILE_APPEND);
}