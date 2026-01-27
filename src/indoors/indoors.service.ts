import { Injectable } from '@nestjs/common';
import { InjectRepository } from '@nestjs/typeorm';
import { Indoor } from './indoor.entity';
import { Repository } from 'typeorm';

@Injectable()
export class IndoorsService {
  constructor(
    @InjectRepository(Indoor)
    private repo: Repository<Indoor>,
  ) {}

  findAll(filters) {
    const qb = this.repo.createQueryBuilder('indoor');

    if (filters.type) {
      qb.andWhere('indoor.type = :type', { type: filters.type });
    }

    return qb.getMany();
  }

  findOne(id: number) {
    return this.repo.findOne({
      where: { id },
      relations: ['reservations'],
    });
  }
}
