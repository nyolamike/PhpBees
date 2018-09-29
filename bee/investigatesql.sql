SELECT 
sells_receipt_items.id as sells_receipt_itemss__id, 
sells_receipt_items.quantity as sells_receipt_itemss__quantity, 
sells_receipt_items.selling_price as sells_receipt_itemss__selling_price, 
SUM( 
    ( sells_receipt_items.quantity * sells_receipt_items.selling_price * 1)
) as sells_receipt_itemss__sub_total, 
sells_receipt.id as sells_receipt_itemss__sells_receipt__id, 
sells_receipt.client_id as sells_receipt_itemss__sells_receipt__client_id, 
sells_receipt.client_payment_id as sells_receipt_itemss__sells_receipt__client_payment_id, 
sells_receipt.section_id as sells_receipt_itemss__sells_receipt__section_id, 
sells_receipt.document_number as sells_receipt_itemss__sells_receipt__document_number, 
sells_receipt.date_time as sells_receipt_itemss__sells_receipt__date_time, 
sells_receipt.vat as sells_receipt_itemss__sells_receipt__vat, 
sells_receipt.discount as sells_receipt_itemss__sells_receipt__discount, 
sells_receipt.notes as sells_receipt_itemss__sells_receipt__notes 
FROM 
sells_receipt_items INNER JOIN sells_receipt ON sells_receipt_items.sells_receipt_id=sells_receipt.id 
WHERE 
(sells_receipt_items.is_deleted = 0) AND (
    ( sells_receipt.date_time >= 5473800) AND ( 
        sells_receipt.date_time <= 5473829
    )
)

SELECT 
sells_receipt_items.quantity as sells_receipt_itemss__quantity, 
sells_receipt_items.selling_price as sells_receipt_itemss__selling_price, 
SUM( 
    ( sells_receipt_items.quantity * sells_receipt_items.selling_price * 1)
) as sells_receipt_itemss__sub_total 
FROM 
sells_receipt_items INNER JOIN sells_receipt ON sells_receipt_items.sells_receipt_id=sells_receipt.id 
WHERE 
(sells_receipt_items.is_deleted = 0) AND (
    ( sells_receipt.date_time >= 5473800) AND ( 
        sells_receipt.date_time <= 5473829
    )
)

SELECT 
SUM( 
    ( sells_receipt_items.quantity * sells_receipt_items.selling_price * 1)
) as sells_receipt_itemss__sub_total 
FROM 
sells_receipt_items INNER JOIN sells_receipt ON sells_receipt_items.sells_receipt_id=sells_receipt.id 
WHERE 
(sells_receipt_items.is_deleted = 0) AND (
    ( sells_receipt.date_time >= 5473800) AND ( 
        sells_receipt.date_time <= 5473829
    )
)

SELECT 
SUM( 
    ( sells_receipt_items.quantity * sells_receipt_items.selling_price * 1)
) as sells_receipt_itemss__sub_total 
FROM 
sells_receipt_items INNER JOIN sells_receipt ON sells_receipt_items.sells_receipt_id=sells_receipt.id 
WHERE 
(sells_receipt_items.is_deleted = 0) AND (
    ( sells_receipt.date_time >= 5473800) AND ( 
        sells_receipt.date_time <= 5473829
    )
)
GROUP BY sells_receipt.date_time

SELECT 
sells_receipt.date_time,
SUM( 
    ( sells_receipt_items.quantity * sells_receipt_items.selling_price * 1)
) as sells_receipt_itemss__sub_total 
FROM 
sells_receipt_items INNER JOIN sells_receipt ON sells_receipt_items.sells_receipt_id=sells_receipt.id 
WHERE 
(sells_receipt_items.is_deleted = 0) AND (
    ( sells_receipt.date_time >= 5473800) AND ( 
        sells_receipt.date_time <= 5473829
    )
)
GROUP BY sells_receipt.date_time