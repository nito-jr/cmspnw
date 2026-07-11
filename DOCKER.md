# Docker Setup Instructions

## Quick Start

1. **Copy the environment file:**
   ```bash
   cp .env.example .env
   ```

2. **Build and start containers:**
   ```bash
   docker-compose up -d
   ```

3. **Access the application:**
   - Open http://localhost in your browser
   - The database will automatically initialize from `database.sql`

## Services

- **Web Service (cmsp_web)**: PHP 8.1 with Apache2
  - Port: 80
  - Volume: `./uploads` for persistent file uploads

- **Database Service (cmsp_db)**: MariaDB Latest
  - Port: 3306
  - Volume: `db_data` for persistent database files

## Configuration

### Environment Variables

Edit `.env` to customize:

```env
DB_HOST=db                    # Database host (use 'db' for Docker)
DB_USER=cmsp_user            # Database user
DB_PASS=cmsp_password        # Database password
DB_NAME=cmsp_db              # Database name
DB_ROOT_PASS=root_password   # Root password
```

## Common Commands

### Start containers
```bash
docker-compose up -d
```

### Stop containers
```bash
docker-compose down
```

### View logs
```bash
docker-compose logs -f web    # PHP/Apache logs
docker-compose logs -f db     # Database logs
```

### Access PHP container
```bash
docker-compose exec web bash
```

### Access database
```bash
docker-compose exec db mysql -u cmsp_user -p cmsp_db
```

### Rebuild after changes
```bash
docker-compose up -d --build
```

### Remove all containers and volumes
```bash
docker-compose down -v
```

## Database Initialization

The `database.sql` file is automatically imported when the database container starts for the first time.

To manually import:
```bash
docker-compose exec db mysql -u cmsp_user -p cmsp_db < database.sql
```

## File Uploads

The `uploads/` directory is mounted as a volume, so uploaded files persist even if containers restart.

## Troubleshooting

**Connection refused?**
- Wait 10-15 seconds for database to fully initialize
- Check: `docker-compose logs db`

**Permission denied on uploads?**
- The container sets proper permissions automatically
- If issues persist: `docker-compose exec web chmod -R 775 /var/www/html/uploads`

**Need to reset everything?**
```bash
docker-compose down -v  # Remove all data
docker-compose up -d    # Start fresh
```

## Production Notes

For production deployment, consider:
- Using `.env.local` for sensitive credentials
- Setting `restart: always` in docker-compose.yml
- Implementing SSL/HTTPS with a reverse proxy (nginx)
- Using environment-specific docker-compose files
- Setting up proper backup strategies for the database volume
