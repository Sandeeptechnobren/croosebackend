# SSH Configuration

How to connect to the Croose server(s) for deployment and maintenance.
Replace every `[FILL IN]` below with the real value — the tech lead must
complete the host, IP, and user before this is usable.

> ⚠️ The frontend code references `api.joincroose.com` and a raw IP
> `68.183.108.227`. These are **observed in source, not confirmed** as the
> deploy target — verify with the tech lead before trusting them.

## ~/.ssh/config entry

Add this block to your local `~/.ssh/config` so you can connect with
`ssh croose-server`:

```
Host croose-server
    HostName [FILL IN — server IP or hostname]
    User [FILL IN — e.g. croose or the cPanel user]
    Port [FILL IN — 22 unless changed]
    IdentityFile ~/.ssh/croose_server_ed25519
    IdentitiesOnly yes
```

## Server user setup (run once, on the server)

```bash
# Create a deploy user (skip if using the existing cPanel/shared-hosting user)
sudo adduser [FILL IN — deploy username]
sudo usermod -aG www-data [FILL IN — deploy username]

# Prepare the authorized_keys file
mkdir -p ~/.ssh && chmod 700 ~/.ssh
touch ~/.ssh/authorized_keys && chmod 600 ~/.ssh/authorized_keys
```

## Generating a key (run on your local machine)

```bash
# Generate a dedicated key for this server (ed25519 preferred)
ssh-keygen -t ed25519 -C "croose-deploy" -f ~/.ssh/croose_server_ed25519

# Copy the PUBLIC key to the server
ssh-copy-id -i ~/.ssh/croose_server_ed25519.pub croose-server
# …or paste the contents of croose_server_ed25519.pub into the server's
# ~/.ssh/authorized_keys manually.
```

## Verifying the connection

```bash
ssh croose-server "whoami && pwd"
```

> 🔒 Never commit private keys, passwords, or the contents of `~/.ssh/` to this
> repo. This file documents the *process* only.
