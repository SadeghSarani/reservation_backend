import { MigrationInterface, QueryRunner } from "typeorm";

export class InitSchema1769418722552 implements MigrationInterface {
    name = 'InitSchema1769418722552'

    public async up(queryRunner: QueryRunner): Promise<void> {
        await queryRunner.query(`ALTER TABLE \`user\` ADD \`role\` enum ('USER', 'ADMIN', 'SUPER_ADMIN') NOT NULL DEFAULT 'USER'`);
        await queryRunner.query(`ALTER TABLE \`indoor\` ADD \`pricePerHour\` int NULL`);
        await queryRunner.query(`ALTER TABLE \`indoor\` ADD \`pricePerMonth\` int NULL`);
        await queryRunner.query(`ALTER TABLE \`indoor\` ADD \`ownerId\` int NULL`);
        await queryRunner.query(`ALTER TABLE \`reservation\` ADD \`price\` int NOT NULL`);
        await queryRunner.query(`ALTER TABLE \`reservation\` DROP FOREIGN KEY \`FK_529dceb01ef681127fef04d755d\``);
        await queryRunner.query(`ALTER TABLE \`reservation\` DROP FOREIGN KEY \`FK_a412191fe525162bc4a13205f3e\``);
        await queryRunner.query(`ALTER TABLE \`reservation\` CHANGE \`startTime\` \`startTime\` datetime NULL`);
        await queryRunner.query(`ALTER TABLE \`reservation\` CHANGE \`endTime\` \`endTime\` datetime NULL`);
        await queryRunner.query(`ALTER TABLE \`reservation\` CHANGE \`month\` \`month\` varchar(255) NULL`);
        await queryRunner.query(`ALTER TABLE \`reservation\` CHANGE \`userId\` \`userId\` int NULL`);
        await queryRunner.query(`ALTER TABLE \`reservation\` CHANGE \`indoorId\` \`indoorId\` int NULL`);
        await queryRunner.query(`ALTER TABLE \`indoor\` ADD CONSTRAINT \`FK_e5dee3d844e1a75be20fbd091bc\` FOREIGN KEY (\`ownerId\`) REFERENCES \`user\`(\`id\`) ON DELETE NO ACTION ON UPDATE NO ACTION`);
        await queryRunner.query(`ALTER TABLE \`reservation\` ADD CONSTRAINT \`FK_529dceb01ef681127fef04d755d\` FOREIGN KEY (\`userId\`) REFERENCES \`user\`(\`id\`) ON DELETE NO ACTION ON UPDATE NO ACTION`);
        await queryRunner.query(`ALTER TABLE \`reservation\` ADD CONSTRAINT \`FK_a412191fe525162bc4a13205f3e\` FOREIGN KEY (\`indoorId\`) REFERENCES \`indoor\`(\`id\`) ON DELETE NO ACTION ON UPDATE NO ACTION`);
    }

    public async down(queryRunner: QueryRunner): Promise<void> {
        await queryRunner.query(`ALTER TABLE \`reservation\` DROP FOREIGN KEY \`FK_a412191fe525162bc4a13205f3e\``);
        await queryRunner.query(`ALTER TABLE \`reservation\` DROP FOREIGN KEY \`FK_529dceb01ef681127fef04d755d\``);
        await queryRunner.query(`ALTER TABLE \`indoor\` DROP FOREIGN KEY \`FK_e5dee3d844e1a75be20fbd091bc\``);
        await queryRunner.query(`ALTER TABLE \`reservation\` CHANGE \`indoorId\` \`indoorId\` int NULL DEFAULT 'NULL'`);
        await queryRunner.query(`ALTER TABLE \`reservation\` CHANGE \`userId\` \`userId\` int NULL DEFAULT 'NULL'`);
        await queryRunner.query(`ALTER TABLE \`reservation\` CHANGE \`month\` \`month\` varchar(255) NULL DEFAULT 'NULL'`);
        await queryRunner.query(`ALTER TABLE \`reservation\` CHANGE \`endTime\` \`endTime\` datetime NULL DEFAULT 'NULL'`);
        await queryRunner.query(`ALTER TABLE \`reservation\` CHANGE \`startTime\` \`startTime\` datetime NULL DEFAULT 'NULL'`);
        await queryRunner.query(`ALTER TABLE \`reservation\` ADD CONSTRAINT \`FK_a412191fe525162bc4a13205f3e\` FOREIGN KEY (\`indoorId\`) REFERENCES \`indoor\`(\`id\`) ON DELETE NO ACTION ON UPDATE NO ACTION`);
        await queryRunner.query(`ALTER TABLE \`reservation\` ADD CONSTRAINT \`FK_529dceb01ef681127fef04d755d\` FOREIGN KEY (\`userId\`) REFERENCES \`user\`(\`id\`) ON DELETE NO ACTION ON UPDATE NO ACTION`);
        await queryRunner.query(`ALTER TABLE \`reservation\` DROP COLUMN \`price\``);
        await queryRunner.query(`ALTER TABLE \`indoor\` DROP COLUMN \`ownerId\``);
        await queryRunner.query(`ALTER TABLE \`indoor\` DROP COLUMN \`pricePerMonth\``);
        await queryRunner.query(`ALTER TABLE \`indoor\` DROP COLUMN \`pricePerHour\``);
        await queryRunner.query(`ALTER TABLE \`user\` DROP COLUMN \`role\``);
    }

}
