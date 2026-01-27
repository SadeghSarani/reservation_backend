"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
exports.AppDataSource = void 0;
require("reflect-metadata");
const typeorm_1 = require("typeorm");
const user_entity_1 = require("./users/user.entity");
const indoor_entity_1 = require("./indoors/indoor.entity");
const reservation_entity_1 = require("./reservations/reservation.entity");
exports.AppDataSource = new typeorm_1.DataSource({
    type: 'mysql',
    host: 'localhost',
    port: 3306,
    username: 'root',
    password: '',
    database: 'reservation',
    entities: [user_entity_1.User, indoor_entity_1.Indoor, reservation_entity_1.Reservation],
    migrations: ['src/migrations/*.ts'],
});
//# sourceMappingURL=data-source.js.map