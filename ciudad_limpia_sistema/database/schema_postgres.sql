
CREATE TABLE users (
    id SERIAL PRIMARY KEY,
    name VARCHAR(100),
    email VARCHAR(100),
    password VARCHAR(255),
    role VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE containers (
    id SERIAL PRIMARY KEY,
    location VARCHAR(255),
    latitude DECIMAL,
    longitude DECIMAL,
    fill_level INTEGER,
    status VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE trucks (
    id SERIAL PRIMARY KEY,
    plate VARCHAR(20),
    driver_name VARCHAR(100),
    status VARCHAR(50)
);

CREATE TABLE routes (
    id SERIAL PRIMARY KEY,
    truck_id INTEGER,
    route_name VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE reports (
    id SERIAL PRIMARY KEY,
    description TEXT,
    latitude DECIMAL,
    longitude DECIMAL,
    status VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
