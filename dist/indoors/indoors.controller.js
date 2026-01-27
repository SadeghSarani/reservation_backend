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
var __param = (this && this.__param) || function (paramIndex, decorator) {
    return function (target, key) { decorator(target, key, paramIndex); }
};
Object.defineProperty(exports, "__esModule", { value: true });
exports.IndoorsController = void 0;
const openapi = require("@nestjs/swagger");
const common_1 = require("@nestjs/common");
const indoors_service_1 = require("./indoors.service");
const swagger_1 = require("@nestjs/swagger");
let IndoorsController = class IndoorsController {
    service;
    constructor(service) {
        this.service = service;
    }
    getAll(query) {
        return this.service.findAll(query);
    }
    getOne(id) {
        return this.service.findOne(+id);
    }
};
exports.IndoorsController = IndoorsController;
__decorate([
    (0, common_1.Get)(),
    openapi.ApiResponse({ status: 200, type: [require("./indoor.entity").Indoor] }),
    __param(0, (0, common_1.Query)()),
    __metadata("design:type", Function),
    __metadata("design:paramtypes", [Object]),
    __metadata("design:returntype", void 0)
], IndoorsController.prototype, "getAll", null);
__decorate([
    (0, common_1.Get)(':id'),
    openapi.ApiResponse({ status: 200, type: Object }),
    __param(0, (0, common_1.Param)('id')),
    __metadata("design:type", Function),
    __metadata("design:paramtypes", [Number]),
    __metadata("design:returntype", void 0)
], IndoorsController.prototype, "getOne", null);
exports.IndoorsController = IndoorsController = __decorate([
    (0, swagger_1.ApiTags)('Indoors'),
    (0, common_1.Controller)('indoors'),
    __metadata("design:paramtypes", [indoors_service_1.IndoorsService])
], IndoorsController);
//# sourceMappingURL=indoors.controller.js.map