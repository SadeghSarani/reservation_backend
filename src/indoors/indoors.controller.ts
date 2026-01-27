import { Controller, Get, Param, Query } from '@nestjs/common';
import { IndoorsService } from './indoors.service';
import { ApiTags } from '@nestjs/swagger';

@ApiTags('Indoors')
@Controller('indoors')
export class IndoorsController {
  constructor(private service: IndoorsService) {}

  @Get()
  getAll(@Query() query) {
    return this.service.findAll(query);
  }

  @Get(':id')
  getOne(@Param('id') id: number) {
    return this.service.findOne(+id);
  }
}
