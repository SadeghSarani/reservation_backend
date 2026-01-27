import { IndoorsService } from './indoors.service';
export declare class IndoorsController {
    private service;
    constructor(service: IndoorsService);
    getAll(query: any): Promise<import("./indoor.entity").Indoor[]>;
    getOne(id: number): Promise<import("./indoor.entity").Indoor | null>;
}
