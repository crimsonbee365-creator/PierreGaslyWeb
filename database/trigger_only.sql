-- ============================================================
-- TRIGGER: Auto-award points when order marked as delivered
-- ============================================================
DROP TRIGGER IF EXISTS `trg_award_points_on_delivery`;

CREATE TRIGGER `trg_award_points_on_delivery`
AFTER UPDATE ON `orders`
FOR EACH ROW
BEGIN
    DECLARE v_size_kg      INT DEFAULT 11;
    DECLARE v_completed    INT DEFAULT 0;
    DECLARE v_tier         VARCHAR(20) DEFAULT 'Bronze';
    DECLARE v_points_rate  INT DEFAULT 100;
    DECLARE v_points       INT DEFAULT 0;
    DECLARE v_total_points INT DEFAULT 0;

    IF NEW.order_status = 'delivered' AND OLD.order_status != 'delivered' THEN

        SELECT COALESCE(p.size_kg, 11) INTO v_size_kg
        FROM products p WHERE p.product_id = NEW.product_id;

        SELECT COUNT(*) INTO v_completed
        FROM orders
        WHERE customer_id = NEW.customer_id AND order_status = 'delivered';

        IF v_completed >= 30 THEN
            SET v_tier = 'Platinum'; SET v_points_rate = 200;
        ELSEIF v_completed >= 15 THEN
            SET v_tier = 'Gold';     SET v_points_rate = 150;
        ELSEIF v_completed >= 5 THEN
            SET v_tier = 'Silver';   SET v_points_rate = 120;
        ELSE
            SET v_tier = 'Bronze';   SET v_points_rate = 100;
        END IF;

        SET v_points = FLOOR(v_points_rate * NEW.quantity * v_size_kg / 11);

        INSERT INTO user_rewards (user_id, total_points, tier)
        VALUES (NEW.customer_id, v_points, v_tier)
        ON DUPLICATE KEY UPDATE
            total_points = total_points + v_points,
            tier         = v_tier;

        INSERT INTO reward_transactions (user_id, order_id, points, type, description)
        VALUES (NEW.customer_id, NEW.order_id, v_points, 'earned',
                CONCAT('Earned ', v_points, ' pts — ', v_tier, ' tier — Order #', NEW.order_number));

    END IF;
END;