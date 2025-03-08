CREATE DATABASE promohub;

USE promohub;

CREATE TABLE products (
    id VARCHAR(50) PRIMARY KEY,
    product_name VARCHAR(255) NOT NULL,
    company_name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    description TEXT,
    package_type VARCHAR(50) NOT NULL,
    package_price DECIMAL(10,2) NOT NULL,
    duration VARCHAR(50) NOT NULL,
    image_url TEXT,
    status VARCHAR(20) DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE transactions (
    transaction_id VARCHAR(50) PRIMARY KEY,
    product_id VARCHAR(50),
    amount DECIMAL(10,2) NOT NULL,
    payment_method VARCHAR(50) NOT NULL,
    status VARCHAR(20) DEFAULT 'Completed',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id)
);