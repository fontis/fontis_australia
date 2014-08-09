<?php

class Doghouse_Australia_Eparcel
{
    const ENCLOSURE = '"';
    const DELIMITER = ',';

    protected $records = array();

    public function addRecord(Doghouse_Australia_Eparcel_Record $record)
    {
        $record->isAddedToEparcel(true);

        return array_push($this->records,$record);
    }

    public function makeCsv($filePath)
    {
        /**
         * File pointer handle
         * @var resource
         */
        $_fp = fopen($filePath, 'w');

        /**
         * Check FP handle is ok
         */
        if ( $_fp == false ) return false;

        /**
         * Cycle through records
         */
        foreach( $this->records as $record )
        {
            fputcsv($_fp,$record->getValues(),self::DELIMITER,self::ENCLOSURE);
        }

        /**
         * Close File pointer
         */
        fclose( $_fp );

        /**
         * Return :)
         */
        return true;
    }
}
