-- LostLink Database Schema
-- Run this file to initialise the database: mysql -u root -p lostlink < schema.sql

CREATE DATABASE IF NOT EXISTS lostlink CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE lostlink;

CREATE TABLE IF NOT EXISTS users (
    user_id  INT          NOT NULL AUTO_INCREMENT,
    email    VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    PRIMARY KEY (user_id)
);

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
    CONSTRAINT fk_item_user FOREIGN KEY (posted_by) REFERENCES users (user_id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS claim_requests (
    request_id  INT  NOT NULL AUTO_INCREMENT,
    item_id     INT  NOT NULL,
    claimed_by  INT  NOT NULL,
    description TEXT NOT NULL,
    proof       BLOB DEFAULT NULL,
    status      ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (request_id),
    CONSTRAINT fk_claim_item FOREIGN KEY (item_id)    REFERENCES items (item_id) ON DELETE CASCADE,
    CONSTRAINT fk_claim_user FOREIGN KEY (claimed_by) REFERENCES users (user_id) ON DELETE CASCADE
);
