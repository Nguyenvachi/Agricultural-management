<?php

namespace App\Constants;

class LookupCode
{
    public const TYPE_USER_ROLE = 'USER_ROLE';
    public const TYPE_PRICE_TYPE = 'PRICE_TYPE';
    public const TYPE_ORDER_TYPE = 'ORDER_TYPE';
    public const TYPE_ORDER_STATUS = 'ORDER_STATUS';
    public const TYPE_TRANSACTION_TYPE = 'TRANSACTION_TYPE';

    public const PRICE_BUY = 'BUY';
    public const PRICE_SELL = 'SELL';

    public const ORDER_PURCHASE = 'PURCHASE_ORDER';
    public const ORDER_SALES = 'SALES_ORDER';
    public const ORDER_INTERNAL_TRANSFER = 'INTERNAL_TRANSFER';
    public const ORDER_RETURN = 'RETURN_ORDER';
    public const ORDER_ADJUSTMENT = 'ADJUSTMENT_ORDER';

    public const ORDER_PENDING = 'PENDING';
    public const ORDER_PROCESSING = 'PROCESSING';
    public const ORDER_COMPLETED = 'COMPLETED';
    public const ORDER_CANCELLED = 'CANCELLED';

    public const TRANSACTION_IMPORT = 'IMPORT';
    public const TRANSACTION_EXPORT = 'EXPORT';

    public const USER_ADMIN = 'ADMIN';
    public const USER_AGENCY = 'AGENCY';
    public const USER_FARMER = 'FARMER';
    public const USER_CUSTOMER = 'CUSTOMER';
}
