import { Module } from '@nestjs/common';
import { IndoorsService } from './indoors.service';
import { IndoorsController } from './indoors.controller';

@Module({
  providers: [IndoorsService],
  controllers: [IndoorsController]
})
export class IndoorsModule {}
