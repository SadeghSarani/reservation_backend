import { IsDateString, IsNumber, IsOptional, Matches } from 'class-validator';

export class CreateReservationDto {
  @IsNumber()
  indoorId: number;

  @IsOptional()
  @IsDateString()
  startTime?: string;

  @IsOptional()
  @IsDateString()
  endTime?: string;

  @IsOptional()
  @Matches(/^\d{4}-\d{2}$/)
  month?: string;
}
