"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
exports.InitSchema1769417140809 = void 0;
class InitSchema1769417140809 {
    name = 'InitSchema1769417140809';
    async up(queryRunner) {
        await queryRunner.query(`CREATE TABLE \`user\` (\`id\` int NOT NULL AUTO_INCREMENT, \`name\` varchar(255) NOT NULL, \`createdAt\` datetime(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6), PRIMARY KEY (\`id\`)) ENGINE=InnoDB`);
        await queryRunner.query(`CREATE TABLE \`indoor\` (\`id\` int NOT NULL AUTO_INCREMENT, \`name\` varchar(255) NOT NULL, \`type\` enum ('FUTSAL', 'TENNIS', 'GYM') NOT NULL, \`isActive\` tinyint NOT NULL DEFAULT 1, \`createdAt\` datetime(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6), PRIMARY KEY (\`id\`)) ENGINE=InnoDB`);
        await queryRunner.query(`CREATE TABLE \`reservation\` (\`id\` int NOT NULL AUTO_INCREMENT, \`type\` enum ('HOURLY', 'MONTHLY') NOT NULL, \`startTime\` datetime NULL, \`endTime\` datetime NULL, \`month\` varchar(255) NULL, \`status\` varchar(255) NOT NULL DEFAULT 'RESERVED', \`createdAt\` datetime(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6), \`userId\` int NULL, \`indoorId\` int NULL, PRIMARY KEY (\`id\`)) ENGINE=InnoDB`);
        await queryRunner.query(`ALTER TABLE \`reservation\` ADD CONSTRAINT \`FK_529dceb01ef681127fef04d755d\` FOREIGN KEY (\`userId\`) REFERENCES \`user\`(\`id\`) ON DELETE NO ACTION ON UPDATE NO ACTION`);
        await queryRunner.query(`ALTER TABLE \`reservation\` ADD CONSTRAINT \`FK_a412191fe525162bc4a13205f3e\` FOREIGN KEY (\`indoorId\`) REFERENCES \`indoor\`(\`id\`) ON DELETE NO ACTION ON UPDATE NO ACTION`);
    }
    async down(queryRunner) {
        await queryRunner.query(`ALTER TABLE \`reservation\` DROP FOREIGN KEY \`FK_a412191fe525162bc4a13205f3e\``);
        await queryRunner.query(`ALTER TABLE \`reservation\` DROP FOREIGN KEY \`FK_529dceb01ef681127fef04d755d\``);
        await queryRunner.query(`DROP TABLE \`reservation\``);
        await queryRunner.query(`DROP TABLE \`indoor\``);
        await queryRunner.query(`DROP TABLE \`user\``);
    }
}
exports.InitSchema1769417140809 = InitSchema1769417140809;
//# sourceMappingURL=1769417140809-InitSchema.js.map