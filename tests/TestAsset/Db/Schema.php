<?php

declare(strict_types=1);

namespace ContenirTest\Db\Model\Tools\TestAsset\Db;

/**
 * The fixture schema in each platform's dialect: users (generated key,
 * every mapped type), order_items (foreign key to users) and user_tag
 * (composite key) and audit_log (no primary key).
 */
final class Schema
{
    /**
     * @return list<string>
     */
    public static function create(Platform $platform): array
    {
        return match ($platform) {
            Platform::Sqlite => [
                'CREATE TABLE users (id INTEGER PRIMARY KEY AUTOINCREMENT, email VARCHAR(255) NOT NULL, '
                    . 'active BOOLEAN NOT NULL DEFAULT 1, created_at DATETIME NOT NULL, birthday DATE, meta JSON, '
                    . 'price DECIMAL(10,2), score REAL NOT NULL DEFAULT 1.5, version INT NOT NULL DEFAULT 1)',
                'CREATE TABLE order_items (id INT PRIMARY KEY, user_id INTEGER REFERENCES users(id), '
                    . 'quantity INT NOT NULL DEFAULT 1)',
                'CREATE TABLE user_tag (user_id INTEGER NOT NULL, tag_id INTEGER NOT NULL, PRIMARY KEY (user_id, tag_id))',
                'CREATE TABLE audit_log (message VARCHAR(100) NOT NULL)',
            ],
            Platform::Mysql => [
                'DROP TABLE IF EXISTS audit_log, order_items, user_tag, users',
                'CREATE TABLE users (id INT AUTO_INCREMENT PRIMARY KEY, email VARCHAR(255) NOT NULL, '
                    . 'active TINYINT(1) NOT NULL DEFAULT 1, created_at DATETIME NOT NULL, birthday DATE NULL, '
                    . 'meta JSON NULL, price DECIMAL(10,2) NULL, score DOUBLE NOT NULL DEFAULT 1.5, '
                    . 'version INT NOT NULL DEFAULT 1)',
                'CREATE TABLE order_items (id INT PRIMARY KEY, user_id INT NULL, quantity INT NOT NULL DEFAULT 1, '
                    . 'CONSTRAINT order_items_user FOREIGN KEY (user_id) REFERENCES users(id))',
                'CREATE TABLE user_tag (user_id INT NOT NULL, tag_id INT NOT NULL, PRIMARY KEY (user_id, tag_id))',
                'CREATE TABLE audit_log (message VARCHAR(100) NOT NULL)',
            ],
            Platform::Pgsql => [
                'DROP TABLE IF EXISTS audit_log, order_items, user_tag, users CASCADE',
                'CREATE TABLE users (id SERIAL PRIMARY KEY, email VARCHAR(255) NOT NULL, '
                    . 'active BOOLEAN NOT NULL DEFAULT TRUE, created_at TIMESTAMP NOT NULL, birthday DATE, meta JSONB, '
                    . 'price NUMERIC(10,2), score DOUBLE PRECISION NOT NULL DEFAULT 1.5, version INT NOT NULL DEFAULT 1)',
                'CREATE TABLE order_items (id INT PRIMARY KEY, user_id INT REFERENCES users(id), '
                    . 'quantity INT NOT NULL DEFAULT 1)',
                'CREATE TABLE user_tag (user_id INT NOT NULL, tag_id INT NOT NULL, PRIMARY KEY (user_id, tag_id))',
                'CREATE TABLE audit_log (message VARCHAR(100) NOT NULL)',
            ],
        };
    }
}
