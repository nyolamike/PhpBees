SELECT 
product.id as products__id, 
product.name as products__name,  
IFNULL(
(
    SELECT 
    SUM(stockout_item.quantity) 
    FROM stockout_item 
    WHERE  
        (stockout_item.is_deleted = 0) AND ( 
            stockout_item.product_id = product.id 
        )
),
0)
-
IFNULL(
(
    SELECT 
    SUM(stockin_item.quantity) 
    FROM stockin_item 
    WHERE  
        (stockin_item.is_deleted = 0) AND ( 
            stockin_item.product_id = product.id 
        )
),
0) as products__nomis, 
product.is_deleted as products__is_deleted,   
unit.id as products__unit__id, 
unit.name as products__unit__name, 
unit.symbol as products__unit__symbol, 
unit.description as products__unit__description 
FROM product  
INNER JOIN unit ON product.unit_id=unit.id   
WHERE  (product.is_deleted = 0)