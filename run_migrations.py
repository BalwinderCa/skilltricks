#!/usr/bin/env python3
"""
Run Laravel Migrations on Server via SSH
"""

import paramiko
from pathlib import Path

# SSH Configuration
SSH_HOST = '217.21.90.136'
SSH_PORT = 65002
SSH_USER = 'u684149649'
SSH_KEY_PATH = Path(__file__).parent / 'id_rsa_skilltricks'
REMOTE_PATH = '/home/u684149649/domains/skilltricksinc.com/public_html/staging'

def run_command(ssh, command, description):
    """Run a command on the remote server"""
    print(f"\n{description}...")
    print(f"  Command: {command}")
    
    stdin, stdout, stderr = ssh.exec_command(command)
    exit_status = stdout.channel.recv_exit_status()
    
    output = stdout.read().decode()
    errors = stderr.read().decode()
    
    if exit_status == 0:
        print("  ✓ Success")
        if output.strip():
            print(f"  Output:\n{output}")
        return True
    else:
        print(f"  ✗ Failed (exit code: {exit_status})")
        if errors.strip():
            print(f"  Errors:\n{errors}")
        if output.strip():
            print(f"  Output:\n{output}")
        return False

def main():
    print("=" * 70)
    print("Run Laravel Migrations on Server")
    print("=" * 70)
    
    if not SSH_KEY_PATH.exists():
        print(f"✗ SSH key not found: {SSH_KEY_PATH}")
        return False
    
    try:
        # Connect to server
        print(f"\n[1] Connecting to {SSH_USER}@{SSH_HOST}:{SSH_PORT}...")
        ssh = paramiko.SSHClient()
        ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
        private_key = paramiko.Ed25519Key.from_private_key_file(str(SSH_KEY_PATH))
        
        ssh.connect(
            hostname=SSH_HOST,
            port=SSH_PORT,
            username=SSH_USER,
            pkey=private_key,
            timeout=30
        )
        print("✓ Connected")
        
        # Change to project directory
        cd_cmd = f"cd {REMOTE_PATH}"
        
        # Check if artisan exists
        print("\n[2] Checking Laravel installation...")
        check_cmd = f"{cd_cmd} && test -f artisan && echo 'artisan found' || echo 'artisan not found'"
        stdin, stdout, stderr = ssh.exec_command(check_cmd)
        result = stdout.read().decode().strip()
        
        if 'not found' in result:
            print("✗ Laravel artisan file not found")
            print(f"  Check if path is correct: {REMOTE_PATH}")
            ssh.close()
            return False
        
        print("✓ Laravel installation found")
        
        # Run migrations
        print("\n[3] Running Laravel migrations...")
        migrate_cmd = f"{cd_cmd} && php artisan migrate --force"
        
        if run_command(ssh, migrate_cmd, "[3] Running migrations"):
            print("\n✓ Migrations completed successfully!")
        else:
            print("\n⚠ Some migrations may have failed")
            print("  Check the output above for details")
        
        # Optional: Show migration status
        print("\n[4] Checking migration status...")
        status_cmd = f"{cd_cmd} && php artisan migrate:status"
        run_command(ssh, status_cmd, "[4] Migration status")
        
        ssh.close()
        print("\n" + "=" * 70)
        print("Migration Process Complete!")
        print("=" * 70)
        return True
        
    except Exception as e:
        print(f"✗ Error: {e}")
        import traceback
        traceback.print_exc()
        return False

if __name__ == '__main__':
    try:
        import paramiko
    except ImportError:
        print("✗ paramiko library not installed")
        print("  Install it with: pip3 install paramiko --break-system-packages")
        exit(1)
    
    main()












