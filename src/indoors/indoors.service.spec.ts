import { Test, TestingModule } from '@nestjs/testing';
import { IndoorsService } from './indoors.service';

describe('IndoorsService', () => {
  let service: IndoorsService;

  beforeEach(async () => {
    const module: TestingModule = await Test.createTestingModule({
      providers: [IndoorsService],
    }).compile();

    service = module.get<IndoorsService>(IndoorsService);
  });

  it('should be defined', () => {
    expect(service).toBeDefined();
  });
});
