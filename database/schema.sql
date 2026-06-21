-- LostLink Database Schema
-- Run this file to initialise the database: mysql -u root -p lostlink < schema.sql
--
-- Demo talking point — relationships at a glance:
--   users (1) ──< items (many)            via items.posted_by
--   items (1) ──< claim_requests (many)   via claim_requests.item_id   (the found item being claimed)
--   users (1) ──< claim_requests (many)   via claim_requests.claimed_by (who's claiming it)
--   items (1) ──< claim_requests (many)   via claim_requests.lost_item_id (optional: the claimant's own lost report)
--   users (1) ──< notifications (many)    via notifications.user_id
-- Every FK below has an explicit ON DELETE behavior — CASCADE where the
-- child row is meaningless without its parent (e.g. a claim can't exist
-- without its item), SET NULL where it's just an optional cross-reference
-- (a claim's link to a lost report).

CREATE DATABASE IF NOT EXISTS lostlink CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE lostlink;

-- One row per registered account. email is UNIQUE so it doubles as the
-- login identifier; password is always a bcrypt hash, never plaintext.
CREATE TABLE IF NOT EXISTS users (
    user_id  INT          NOT NULL AUTO_INCREMENT,
    email    VARCHAR(191) NOT NULL UNIQUE,
    name     VARCHAR(255) NOT NULL DEFAULT '',
    password VARCHAR(255) NOT NULL,
    PRIMARY KEY (user_id)
);

-- A single lost OR found report. report_type/status together drive the
-- whole lifecycle: a 'found' item starts 'active' and flips to 'claimed'
-- once a claim against it is approved and marked received (see
-- ClaimController::markReceived).
CREATE TABLE IF NOT EXISTS items (
    item_id     INT           NOT NULL AUTO_INCREMENT,
    title       VARCHAR(255)  NOT NULL,
    description TEXT          NOT NULL,
    category    VARCHAR(100)  NOT NULL,
    location    VARCHAR(255)  NOT NULL,
    date        DATETIME      NOT NULL,
    report_type ENUM('lost', 'found') NOT NULL,
    status      ENUM('active', 'claimed') NOT NULL DEFAULT 'active',
    image_path  VARCHAR(500)  DEFAULT NULL,
    posted_by   INT           NOT NULL,
    PRIMARY KEY (item_id),
    -- Deleting a user deletes everything they posted — no orphaned items.
    CONSTRAINT fk_item_user FOREIGN KEY (posted_by) REFERENCES users (user_id) ON DELETE CASCADE
);

-- The claim/verification workflow: someone (claimed_by) asserts a found
-- item (item_id) is theirs, optionally tying it back to their own lost
-- report (lost_item_id). status walks pending -> approved/rejected ->
-- received — see ClaimController for every transition.
CREATE TABLE IF NOT EXISTS claim_requests (
    request_id  INT  NOT NULL AUTO_INCREMENT,
    item_id     INT  NOT NULL,
    claimed_by  INT  NOT NULL,
    lost_item_id INT DEFAULT NULL,
    description TEXT NOT NULL,
    proof_path  VARCHAR(500) DEFAULT NULL,
    status ENUM('pending', 'approved', 'rejected', 'received') NOT NULL DEFAULT 'pending',
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (request_id),
    -- Claim is meaningless without its item/claimant, so both cascade.
    CONSTRAINT fk_claim_item      FOREIGN KEY (item_id)      REFERENCES items (item_id) ON DELETE CASCADE,
    CONSTRAINT fk_claim_user      FOREIGN KEY (claimed_by)   REFERENCES users (user_id) ON DELETE CASCADE,
    -- Optional cross-reference: if the linked lost report gets deleted,
    -- just null this out rather than deleting the whole claim.
    CONSTRAINT fk_claim_lost_item FOREIGN KEY (lost_item_id) REFERENCES items (item_id) ON DELETE SET NULL
);

-- One row per in-app notification (claim submitted/approved/rejected,
-- item received, possible match found — see NotificationController and
-- every NotificationController::notify() call site across the controllers).
-- is_read is what the navbar bell's unread badge counts.
CREATE TABLE IF NOT EXISTS notifications (
    notification_id INT          NOT NULL AUTO_INCREMENT,
    user_id          INT          NOT NULL,
    type             VARCHAR(50)  NOT NULL,
    message          TEXT         NOT NULL,
    link             VARCHAR(255) DEFAULT NULL,
    is_read          TINYINT(1)   NOT NULL DEFAULT 0,
    created_at       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (notification_id),
    CONSTRAINT fk_notification_user FOREIGN KEY (user_id) REFERENCES users (user_id) ON DELETE CASCADE
);
