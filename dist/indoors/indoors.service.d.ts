import { Indoor } from './indoor.entity';
import { Repository } from 'typeorm';
export declare class IndoorsService {
    private repo;
    constructor(repo: Repository<Indoor>);
    findAll(filters: any): Promise<Indoor[]>;
    findOne(id: number): Promise<Indoor | null>;
}
