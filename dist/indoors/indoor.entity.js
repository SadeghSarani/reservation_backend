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
exports.Indoor = void 0;
const openapi = require("@nestjs/swagger");
const typeorm_1 = require("typeorm");
const indoor_type_enum_1 = require("../common/enums/indoor-type.enum");
const user_entity_1 = require("../users/user.entity");
let Indoor = class Indoor {
    id;
    name;
    type;
    isActive;
    createdAt;
    owner;
    pricePerHour;
    pricePerMonth;
    static _OPENAPI_METADATA_FACTORY() {
        return { id: { required: true, type: () => Number }, name: { required: true, type: () => String }, type: { required: true, enum: require("../common/enums/indoor-type.enum").IndoorType }, isActive: { required: true, type: () => Boolean }, createdAt: { required: true, type: () => Date }, owner: { required: true, type: () => require("../users/user.entity").User }, pricePerHour: { required: true, type: () => Number }, pricePerMonth: { required: true, type: () => Number } };
    }
};
exports.Indoor = Indoor;
__decorate([
    (0, typeorm_1.PrimaryGeneratedColumn)(),
    __metadata("design:type", Number)
], Indoor.prototype, "id", void 0);
__decorate([
    (0, typeorm_1.Column)(),
    __metadata("design:type", String)
], Indoor.prototype, "name", void 0);
__decorate([
    (0, typeorm_1.Column)({ type: 'enum', enum: indoor_type_enum_1.IndoorType }),
    __metadata("design:type", String)
], Indoor.prototype, "type", void 0);
__decorate([
    (0, typeorm_1.Column)({ default: true }),
    __metadata("design:type", Boolean)
], Indoor.prototype, "isActive", void 0);
__decorate([
    (0, typeorm_1.CreateDateColumn)(),
    __metadata("design:type", Date)
], Indoor.prototype, "createdAt", void 0);
__decorate([
    (0, typeorm_1.ManyToOne)(() => user_entity_1.User),
    __metadata("design:type", user_entity_1.User)
], Indoor.prototype, "owner", void 0);
__decorate([
    (0, typeorm_1.Column)({ type: 'int', nullable: true }),
    __metadata("design:type", Number)
], Indoor.prototype, "pricePerHour", void 0);
__decorate([
    (0, typeorm_1.Column)({ type: 'int', nullable: true }),
    __metadata("design:type", Number)
], Indoor.prototype, "pricePerMonth", void 0);
exports.Indoor = Indoor = __decorate([
    (0, typeorm_1.Entity)()
], Indoor);
//# sourceMappingURL=indoor.entity.js.map