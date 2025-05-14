CREATE TABLE IF NOT EXISTS pc_status (
    id INT AUTO_INCREMENT PRIMARY KEY,
    room VARCHAR(50) NOT NULL,
    pc_number INT NOT NULL,
    status ENUM('available', 'in_use', 'maintenance') DEFAULT 'available',
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_pc (room, pc_number)
); 