<?php
class Doghouse_Australia_Eparcel
{
    const ENCLOSURE = '"';
    const DELIMITER = ',';
    private $_cRecord = array(
        "C_CONSIGNMENT_ID",
        "C_POST_CHARGE_TO_ACCOUNT",
        "C_CHARGE_CODE",
        "C_MERCHANT_CONSIGNEE_CODE",
        "C_CONSIGNEE_NAME",
        "C_CONSIGNEE_BUSINESS_NAME",
        "C_CONSIGNEE_ADDRESS_1",
        "C_CONSIGNEE_ADDRESS_2",
        "C_CONSIGNEE_ADDRESS_3",
        "C_CONSIGNEE_ADDRESS_4",
        "C_CONSIGNEE_SUBURB",
        "C_CONSIGNEE_STATE_CODE",
        "C_CONSIGNEE_POSTCODE",
        "C_CONSIGNEE_COUNTRY_CODE",
        "C_CONSIGNEE_PHONE_NUMBER",
        "C_PHONE_PRINT_REQUIRED",
        "C_CONSIGNEE_FAX_NUMBER",
        "C_DELIVERY_INSTRUCTION",
        "C_SIGNATURE_REQUIRED",
        "C_PART_DELIVERY",
        "C_COMMENTS",
        "C_ADD_TO_ADDRESS_BOOK",
        "C_CTC_AMOUNT",
        "C_REF",
        "C_REF_PRINT_REQUIRED",
        "C_REF2",
        "C_REF2_PRINT_REQUIRED",
        "C_CHARGEBACK_ACCOUNT",
        "C_RECURRING_CONSIGNMENT",
        "C_RETURN_NAME",
        "C_RETURN_ADDRESS_1",
        "C_RETURN_ADDRESS_2",
        "C_RETURN_ADDRESS_3",
        "C_RETURN_ADDRESS_4",
        "C_RETURN_SUBURB",
        "C_RETURN_STATE_CODE",
        "C_RETURN_POSTCODE",
        "C_RETURN_COUNTRY_CODE",
        "C_REDIR_COMPANY_NAME",
        "C_REDIR_NAME",
        "C_REDIR_ADDRESS_1",
        "C_REDIR_ADDRESS_2",
        "C_REDIR_ADDRESS_3",
        "C_REDIR_ADDRESS_4",
        "C_REDIR_SUBURB",
        "C_REDIR_STATE_CODE",
        "C_REDIR_POSTCODE",
        "C_REDIR_COUNTRY_CODE",
        "C_MANIFEST_ID",
        "C_CONSIGNEE_EMAIL",
        "C_EMAIL_NOTIFICATION",
        "C_APCN",
        "C_SURVEY",
        "C_DELIVERY_SUBSCRIPTION",
        "C_EMBARGO_DATE",
        "C_SPECIFIED_DATE",
        "C_DELIVER_DAY",
        "C_DO_NOT_DELIVER_DAY",
        "C_DELIVERY_WINDOW",
        "C_CDP_LOCATION",
        "C_IMPORTERREFNBR",
        "C_SENDER_NAME",
        "C_SENDER_CUSTOMS_REFERENCE",
        "C_SENDER_BUSINESS_NAME",
        "C_SENDER_ADDRESS_LINE1",
        "C_SENDER_ADDRESS_LINE2",
        "C_SENDER_ADDRESS_LINE3",
        "C_SENDER_SUBURB_CITY",
        "C_SENDER_STATE_CODE",
        "C_SENDER_POSTCODE",
        "C_SENDER_COUNTRY_CODE",
        "C_SENDER_PHONE_NUMBER",
        "C_SENDER_EMAIL",
        "C_RTN_LABEL"
    );
    private $_aRecord = array(
        "A_ACTUAL_CUBIC_WEIGHT",
        "A_LENGTH",
        "A_WIDTH",
        "A_HEIGHT",
        "A_NUMBER_IDENTICAL_ARTS",
        "A_CONSIGNMENT_ARTICLE_TYPE_DESCRIPTION",
        "A_IS_DANGEROUS_GOODS",
        "A_IS_TRANSIT_COVER_REQUIRED",
        "A_TRANSIT_COVER_AMOUNT",
        "A_CUSTOMS_DECLARED_VALUE",
        "A_CLASSIFICATION_EXPLANATION",
        "A_EXPORT_CLEARANCE_NUMBER",
        "A_IS_RETURN_SURFACE",
        "A_IS_RETURN_AIR",
        "A_IS_ABANDON",
        "A_IS_REDIRECT_SURFACE",
        "A_IS_REDIRECT_AIR",
        "A_PROD_CLASSIFICATION",
        "A_IS_COMMERCIAL_VALUE"
    );
    private $_gRecord = array(
        "G_ORIGIN_COUNTRY_CODE",
        "G_HS_TARIFF",
        "G_DESCRIPTION",
        "G_PRODUCT_TYPE",
        "G_PRODUCT_CLASSIFICATION",
        "G_QUANTITY",
        "G_WEIGHT",
        "G_UNIT_VALUE",
        "G_TOTAL_VALUE"
    );
    private $_keyRecord = array(
        "IGNORED",
        "OPTIONAL",
        "MANDATORY",
        "MANDATORY/OPTIONAL REFER TO GUIDE",
        "MANDATORY/OPTIONAL REFER TO GUIDE",
        "OPTIONAL",
        "MANDATORY/OPTIONAL REFER TO GUIDE",
        "OPTIONAL",
        "OPTIONAL",
        "OPTIONAL",
        "MANDATORY/OPTIONAL REFER TO GUIDE",
        "MANDATORY/OPTIONAL REFER TO GUIDE",
        "MANDATORY/OPTIONAL REFER TO GUIDE",
        "MANDATORY/OPTIONAL REFER TO GUIDE",
        "MANDATORY/OPTIONAL REFER TO GUIDE",
        "OPTIONAL",
        "OPTIONAL",
        "OPTIONAL",
        "OPTIONAL",
        "OPTIONAL",
        "OPTIONAL",
        "OPTIONAL",
        "OPTIONAL",
        "OPTIONAL",
        "OPTIONAL",
        "OPTIONAL",
        "OPTIONAL",
        "OPTIONAL",
        "OPTIONAL",
        "OPTIONAL",
        "OPTIONAL",
        "OPTIONAL",
        "OPTIONAL",
        "OPTIONAL",
        "OPTIONAL",
        "OPTIONAL",
        "OPTIONAL",
        "OPTIONAL",
        "OPTIONAL",
        "OPTIONAL",
        "OPTIONAL",
        "OPTIONAL",
        "OPTIONAL",
        "OPTIONAL",
        "OPTIONAL",
        "OPTIONAL",
        "OPTIONAL",
        "OPTIONAL",
        "IGNORED",
        "MANDATORY/OPTIONAL REFER TO GUIDE",
        "OPTIONAL",
        "OPTIONAL",
        "OPTIONAL",
        "OPTIONAL",
        "OPTIONAL",
        "OPTIONAL",
        "OPTIONAL",
        "OPTIONAL",
        "OPTIONAL",
        "OPTIONAL",
        "OPTIONAL",
        "OPTIONAL",
        "OPTIONAL",
        "OPTIONAL",
        "OPTIONAL",
        "OPTIONAL",
        "OPTIONAL",
        "OPTIONAL",
        "OPTIONAL",
        "OPTIONAL",
        "OPTIONAL",
        "OPTIONAL",
        "OPTIONAL",
        "OPTIONAL",
        "MANDATORY",
        "MANDATORY/OPTIONAL REFER TO GUIDE",
        "MANDATORY/OPTIONAL REFER TO GUIDE",
        "MANDATORY/OPTIONAL REFER TO GUIDE",
        "OPTIONAL",
        "OPTIONAL",
        "OPTIONAL",
        "OPTIONAL",
        "OPTIONAL",
        "MANDATORY/OPTIONAL REFER TO GUIDE",
        "MANDATORY/OPTIONAL REFER TO GUIDE",
        "OPTIONAL",
        "OPTIONAL",
        "OPTIONAL",
        "OPTIONAL",
        "OPTIONAL",
        "OPTIONAL",
        "MANDATORY/OPTIONAL REFER TO GUIDE",
        "OPTIONAL",
        "MANDATORY/OPTIONAL REFER TO GUIDE",
        "MANDATORY/OPTIONAL REFER TO GUIDE",
        "MANDATORY",
        "OPTIONAL",
        "OPTIONAL",
        "MANDATORY/OPTIONAL REFER TO GUIDE",
        "MANDATORY/OPTIONAL REFER TO GUIDE",
        "MANDATORY/OPTIONAL REFER TO GUIDE",
        "MANDATORY/OPTIONAL REFER TO GUIDE"
    );
    /** @var Doghouse_Australia_Eparcel_Record[] */
    protected $records = array();
    public function addRecord(Doghouse_Australia_Eparcel_Record $record)
    {
        $record->isAddedToEparcel(true);
        return array_push($this->records, $record);
    }
    public function makeCsv($filePath)
    {
        /**
         * List of valid eparcel record types
         */
        $recordTypes = array('C','A','G');
        /**
         * Array that aggregates the total fields for export row
         */
        $csvRow = array();
        /**
         * File pointer handle
         * @var resource
         */
        $fp = fopen($filePath, 'w');
        // Check FP handle is ok
        if ( $fp == false ) {
            return false;
        }
        // Add CSV header
        fputcsv($fp, array_merge($this->_cRecord, $this->_aRecord, $this->_gRecord), self::DELIMITER, self::ENCLOSURE);
        fputcsv($fp, $this->_keyRecord, self::DELIMITER, self::ENCLOSURE);
        // Cycle through records
        foreach ($this->records as $i => $record) {
            // Get array of field values set from class
            $recordArray = $record->getValues();
            // Verify correct record
            if ($recordTypes[$i % 3] == $recordArray[0]) {
                // Rectify consignment record charge code field: free shipping set to standard shipping charge code for individuals
                if ($recordTypes[$i % 3] == 'C' && $recordArray[3] == 'FREESHIPPING') {
                    $recordArray[3] = Mage::getStoreConfig('doghouse_eparcelexport/charge_codes/default_charge_code_individual');
                }
                // Remove record type value, unneeded in eparcel import
                unset($recordArray[0]);
                // Add current record to the row
                $csvRow = array_merge($csvRow, $recordArray);
                // Once all CSV fields are added to row, write row to file
                if ($recordTypes[$i % 3] == 'G') {
                    fputcsv($fp, $csvRow, self::DELIMITER, self::ENCLOSURE);
                    // Reset
                    $csvRow = array();
                }
            }
        }
        fclose($fp);
        return true;
    }
}
