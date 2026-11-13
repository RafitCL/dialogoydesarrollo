-- Usuario administrador inicial.
-- Genera el hash con: php -r "echo password_hash('TuClaveSegura1!', PASSWORD_DEFAULT), PHP_EOL;"
-- y reemplaza el valor de abajo.
INSERT INTO usuarios (nombres, ap_paterno, ap_materno, email, password_hash, rol)
VALUES ('Admin', 'Principal', 'DDP', 'admin@dialogoydesarrollo.com.pe',
        '$2y$12$s7J39YMmikpBwM7eK.HYGOPVJL2DwB1G5tLcSs14e7cjeE3dSmUpW', 'admin');