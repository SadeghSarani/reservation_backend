import { BadRequestException, Injectable } from '@nestjs/common';
import { InjectRepository } from '@nestjs/typeorm';
import { Repository } from 'typeorm';

import { ReservationStrategy } from './reservation.strategy';
import { ReservationType } from '../../common/enums/reservation-type.enum';
import { Reservation } from '../reservation.entity';
import { CreateReservationDto } from '../dto/create-reservation.dto';
import { Indoor } from '../../indoors/indoor.entity';

@Injectable()
export class HourlyReservationStrategy implements ReservationStrategy {
  constructor(
    @InjectRepository(Reservation)
    private readonly reservationRepo: Repository<Reservation>,
  ) {}

  async validate(dto: CreateReservationDto, indoor: Indoor): Promise<void> {
    if (!dto.startTime || !dto.endTime) {
      throw new BadRequestException('Start and end time required');
    }

    if (new Date(dto.startTime) >= new Date(dto.endTime)) {
      throw new BadRequestException('End time must be after start time');
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
      type: ReservationType.HOURLY,
      startTime: dto.startTime,
      endTime: dto.endTime,
    });

    return this.reservationRepo.save(reservation);
  }
}
