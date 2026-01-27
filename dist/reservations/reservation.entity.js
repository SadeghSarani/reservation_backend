"use strict";
var __decorate = (this && this.__decorate) || function (decorators, target, key, desc) {
    var c = arguments.length, r = c < 3 ? target : desc === null ? desc = Object.getOwnPropertyDescriptor(target, key) : desc, d;
    if (typeof Reflect === "object" && typeof Reflect.decorate === "function") r = Reflect.decorate(decorators, target, key, desc);
    else for (var i = decorators.length - 1; i >= 0; i--) if (d = decorators[i]) r = (c < 3 ? d(r) : c > 3 ? d(target, key, r) : d(target, key)) || r;
    return c > 3 && r && Object.defineProperty(target, key, r), r;
};
var __metadata = (this && this.__metadata) || function (k, v) {
    if (typeof Reflect === "object" && typeof Reflect.metadata === "function") return Reflect.metadata(k, v);
};
Object.defineProperty(exports, "__esModule", { value: true });
exports.Reservation = void 0;
const openapi = require("@nestjs/swagger");
const typeorm_1 = require("typeorm");
const user_entity_1 = require("../users/user.entity");
const indoor_entity_1 = require("../indoors/indoor.entity");
const reservation_type_enum_1 = require("../common/enums/reservation-type.enum");
let Reservation = class Reservation {
    id;
    user;
    indoor;
    type;
    startTime;
    endTime;
    month;
    status;
    createdAt;
    price;
    static _OPENAPI_METADATA_FACTORY() {
        return { id: { required: true, type: () => Number }, user: { required: true, type: () => require("../users/user.entity").User }, indoor: { required: true, type: () => require("../indoors/indoor.entity").Indoor }, type: { required: true, enum: require("../common/enums/reservation-type.enum").ReservationType }, startTime: { required: true, type: () => Date }, endTime: { required: true, type: () => Date }, month: { required: true, type: () => String }, status: { required: true, type: () => String }, createdAt: { required: true, type: () => Date }, price: { required: true, type: () => Number } };
    }
};
exports.Reservation = Reservation;
__decorate([
    (0, typeorm_1.PrimaryGeneratedColumn)(),
    __metadata("design:type", Number)
], Reservation.prototype, "id", void 0);
__decorate([
    (0, typeorm_1.ManyToOne)(() => user_entity_1.User),
    __metadata("design:type", user_entity_1.User)
], Reservation.prototype, "user", void 0);
__decorate([
    (0, typeorm_1.ManyToOne)(() => indoor_entity_1.Indoor),
    __metadata("design:type", indoor_entity_1.Indoor)
], Reservation.prototype, "indoor", void 0);
__decorate([
    (0, typeorm_1.Column)({ type: 'enum', enum: reservation_type_enum_1.ReservationType }),
    __metadata("design:type", String)
], Reservation.prototype, "type", void 0);
__decorate([
    (0, typeorm_1.Column)({ type: 'datetime', nullable: true }),
    __metadata("design:type", Date)
], Reservation.prototype, "startTime", void 0);
__decorate([
    (0, typeorm_1.Column)({ type: 'datetime', nullable: true }),
    __metadata("design:type", Date)
], Reservation.prototype, "endTime", void 0);
__decorate([
    (0, typeorm_1.Column)({ nullable: true }),
    __metadata("design:type", String)
], Reservation.prototype, "month", void 0);
__decorate([
    (0, typeorm_1.Column)({ default: 'RESERVED' }),
    __metadata("design:type", String)
], Reservation.prototype, "status", void 0);
__decorate([
    (0, typeorm_1.CreateDateColumn)(),
    __metadata("design:type", Date)
], Reservation.prototype, "createdAt", void 0);
__decorate([
    (0, typeorm_1.Column)({ type: 'int' }),
    __metadata("design:type", Number)
], Reservation.prototype, "price", void 0);
exports.Reservation = Reservation = __decorate([
    (0, typeorm_1.Entity)()
], Reservation);
//# sourceMappingURL=reservation.entity.js.map