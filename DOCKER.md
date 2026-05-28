# FOG Project — Containerised Deployment

This deploys FOG Project v1.5.10.x in Docker containers using s6-overlay
as the process supervisor.

## Architecture

```
┌─────────────────────────────────────────────────────┐
│                  Docker Network                      │
│                  172.20.0.0/24                       │
│                                                     │
│  ┌────────────┐  ┌────────────┐  ┌──────────────┐  │
│  │  fog-db    │  │ fog-master │  │ fog-storage  │  │
│  │ MariaDB    │  │ Apache/PHP │  │    -node     │  │
│  │ :3306      │  │ TFTP/NFS   │  │ NFS/FTP      │  │
│  │            │  │ FTP/Svcs   │  │              │  │
│  └────────────┘  └────────────┘  └──────────────┘  │
│   172.20.0.2      172.20.0.10     172.20.0.11       │
└─────────────────────────────────────────────────────┘
```

## Quick Start

```bash
# Copy environment file
cp .env.example .env

# Edit .env — set FOG_IP to your Docker host's LAN IP
# (this is what PXE clients will boot from)
nano .env

# Build and start
docker compose up -d --build

# Watch logs
docker compose logs -f fog-master
```

## First Run

1. After containers start, FOG will run the database schema migration automatically.
2. Access the web UI at `http://<FOG_IP>/fog/management`
3. Default credentials: `fog` / `password`
4. Register the storage node via the UI (if using `fog-storage-node`)

## Services per Container

| Service               | Port    | Protocol | Purpose                  |
| --------------------- | ------- | -------- | ------------------------ |
| Apache + PHP-FPM      | 80, 443 | TCP      | Web UI & API             |
| tftpd-hpa             | 69      | UDP      | iPXE boot files          |
| vsftpd                | 21      | TCP      | Image upload/download    |
| NFS (rpc.nfsd)        | 2049    | TCP      | Image serving to clients |
| NFS (rpc.mountd)      | 20048   | TCP      | NFS mount requests       |
| FOG Task Scheduler    | —       | —        | PHP daemon               |
| FOG Image Replicator  | —       | —        | PHP daemon               |
| FOG Snapin Replicator | —       | —        | PHP daemon               |
| FOG Multicast Manager | —       | —        | PHP daemon               |
| FOG Snapin Hash       | —       | —        | PHP daemon               |
| FOG Image Size        | —       | —        | PHP daemon               |
| FOG Ping Hosts        | —       | —        | PHP daemon               |

## Environment Variables

| Variable           | Default            | Description                                          |
| ------------------ | ------------------ | ---------------------------------------------------- |
| `FOG_IP`           | —                  | IP that PXE clients reach FOG on                     |
| `FOG_DB_HOST`      | —                  | Database hostname                                    |
| `FOG_DB_NAME`      | `fog`              | Database name                                        |
| `FOG_DB_USER`      | `fogstorage`       | Database user                                        |
| `FOG_DB_PASS`      | —                  | Database password                                    |
| `FOG_DB_ROOT_PASS` | —                  | MariaDB root password (master only, for schema init) |
| `FOG_STORAGE_NODE` | `0`                | `0` = master, `1` = storage node                     |
| `FOG_INTERFACE`    | `eth0`             | Network interface for multicast                      |
| `FOG_USER`         | `fogproject`       | System user for FTP/NFS                              |
| `FOG_USER_PASS`    | `fogproject`       | System user password                                 |
| `FOG_HOSTNAME`     | container hostname | Advertised hostname                                  |
| `FOG_HTTP_PROTO`   | `http`             | `http` or `https`                                    |

## Volumes

| Volume        | Mount              | Purpose              |
| ------------- | ------------------ | -------------------- |
| `fog-db-data` | `/var/lib/mysql`   | MariaDB data         |
| `fog-images`  | `/images`          | Captured disk images |
| `fog-snapins` | `/opt/fog/snapins` | Snapin packages      |

## Production Notes (k3s/Rancher)

- Use `hostNetwork: true` on the pod so PXE/TFTP/NFS work without NAT
- Mount a PVC at `/images` for shared image storage
- Set `FOG_IP` to the node's LAN IP
- NFS kernel server requires `SYS_ADMIN` capability or a privileged container
- Consider nfs-ganesha (userspace NFS) to avoid the kernel module requirement

## Rebuilding

```bash
docker compose down
docker compose build --no-cache
docker compose up -d
```
