import { ReservationStrategy } from './reservation.strategy';
import { BadRequestException, Injectable } from '@nestjs/common';
import { InjectRepository } from '@nestjs/typeorm';
import { Reservation } from '../reservation.entity';
import { Repository } from 'typeorm';
import { ReservationType } from '../../common/enums/reservation-type.enum';
import { CreateReservationDto } from '../dto/create-reservation.dto';
import { Indoor } from '../../indoors/indoor.entity';

@Injectable()
export class MonthlyReservationStrategy implements ReservationStrategy {
  constructor(
    @InjectRepository(Reservation)
    private reservationRepo: Repository<Reservation>,
  ) {}

  async validate(dto: CreateReservationDto, indoor: Indoor): Promise<void> {
    if (!dto.month) {
      throw new BadRequestException('Month is required');
    }
  }

  async create(
    dto: CreateReservationDto,
    userId: number,
    indoor: Indoor,
  ): Promise<Reservation> {
    const reservation = this.reservationRepo.create({
      user: { id: userId },
      indoor: { id: indoor.id },
      type: ReservationType.MONTHLY,
      month: dto.month,
    });

    return this.reservationRepo.save(reservation);
  }

}
