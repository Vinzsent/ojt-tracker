CREATE TABLE IF NOT EXISTS time_records (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    time_in DATETIME NOT NULL,
    time_out DATETIME,
    date DATE NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(user_id)
);
