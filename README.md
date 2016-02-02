Fontis Australia Extension
==========================

This extension provides essential functionality for Australian stores, including Australia Post shipping, direct deposit and BPAY payment methods, and adds Australian regions and postcodes.

Further documentation is available from the [Fontis Australia Extension](http://www.fontis.com.au/magento/extensions/australia) page on our website.

## Multi-Warehouse

As of 2.4.0 we added support for Multi Warehouse Extension in the eParcel Shipping Method. The eParcel rates can be specified for each of the Warehouse separately (eg. from Sydney - Warehouse ID = 1, from Melbourne Warehouse ID = 2).  

The import file for eParcel rates can have one additional column for the Warehouse ID (called stock_id in code). The complete import file structure:
"Country", "Region/State", "Postcodes", "Weight from", "Weight to", "Parcel Cost", "Cost Per Kg", "Delivery Type", "Charge Code Ind", "Charge Code Bus", "Warehouse ID"

Leave Warehouse ID empty when not using Multi Warehouse Extension. 

## Importing eParcel feeds into Australia Post

If you receive an error stating `The file contains the following invalid headers`, you need to ensure you have configured eParcel itself to use the correct format.

To do this:

1. Login to eParcel
2. Visit `Administration` > `Merchant Location Details`.
3. Under `Define Consignment CSV Import File Format`, ensure that `Old Consignment CSV Import File Format` is checked.

![australia post](https://cloud.githubusercontent.com/assets/181919/12737884/34a209dc-c9b0-11e5-9505-515994960078.png)

> Note, this should only affect new eParcel clients and not existing ones.
