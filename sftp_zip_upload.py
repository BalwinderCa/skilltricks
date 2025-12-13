#!/usr/bin/env python3
"""
Zip and Upload to SFTP - Much faster than individual file uploads
"""

import paramiko
import zipfile
import os
from pathlib import Path
from datetime import datetime

# SSH/SFTP Configuration
SSH_HOST = '217.21.90.136'
SSH_PORT = 65002
SSH_USER = 'u684149649'
SSH_KEY_PATH = Path(__file__).parent / 'id_rsa_skilltricks'
REMOTE_PATH = '/home/u684149649/domains/skilltricksinc.com/public_html/staging'

LOCAL_ROOT = Path(__file__).parent
ZIP_NAME = 'skilltricks_deploy.zip'

# Directories/files to exclude from zip
EXCLUDE = {
    '.git', '.gitignore', '.DS_Store', 'node_modules', 'vendor', '.env',
    'storage/logs', 'storage/framework/cache', 'storage/framework/sessions',
    'storage/framework/views', 'bootstrap/cache', '.idea', '.vscode',
    'test_ftp.py', 'test_ftp_detailed.py', 'test_ftp_plain.py', 'test_ftp_ip.py',
    'ftp_connect.py', 'ftp_explore.py', 'ftp_navigate.py', 'ftp_create_staging.py',
    'ftp_sync.py', 'ftp_upload.py', 'ftp_upload_robust.py', 'ftp_upload_final.py',
    'sftp_upload.py', 'sftp_zip_upload.py', 'error_log', 'database.sql',
    '*.log', '.ftpquota', 'upload_log.txt', 'id_rsa_skilltricks', 
    'id_rsa_skilltricks.pub', 'SSH_SETUP_INSTRUCTIONS.md', 'PUBLIC_KEY.txt',
    'skilltricks_deploy.zip'  # Exclude the zip itself
}

def should_include(path):
    """Check if file/directory should be included in zip"""
    rel_path = str(path.relative_to(LOCAL_ROOT))
    
    for exclude in EXCLUDE:
        if exclude in rel_path or rel_path.endswith(exclude):
            return False
    
    parts = rel_path.split('/')
    for part in parts:
        if part.startswith('.') and part not in ['.htaccess', '.env.example', '.editorconfig', '.gitattributes', '.styleci.yml']:
            return False
    
    return True

def create_zip():
    """Create zip file of project"""
    print("=" * 70)
    print("Creating ZIP Archive")
    print("=" * 70)
    
    zip_path = LOCAL_ROOT / ZIP_NAME
    
    # Remove old zip if exists
    if zip_path.exists():
        print(f"  Removing old {ZIP_NAME}...")
        zip_path.unlink()
    
    print(f"\n[1] Creating {ZIP_NAME}...")
    file_count = 0
    
    with zipfile.ZipFile(zip_path, 'w', zipfile.ZIP_DEFLATED) as zipf:
        for root, dirs, files in os.walk(LOCAL_ROOT):
            # Filter directories
            dirs[:] = [d for d in dirs if should_include(Path(root) / d)]
            
            for file in files:
                file_path = Path(root) / file
                if should_include(file_path):
                    rel_path = file_path.relative_to(LOCAL_ROOT)
                    zipf.write(file_path, rel_path)
                    file_count += 1
                    if file_count % 100 == 0:
                        print(f"  Added {file_count} files...")
    
    zip_size = zip_path.stat().st_size / (1024 * 1024)  # Size in MB
    print(f"\n✓ ZIP created: {ZIP_NAME}")
    print(f"  Files: {file_count}")
    print(f"  Size: {zip_size:.2f} MB")
    
    return zip_path

def upload_zip(zip_path):
    """Upload zip file via SFTP"""
    print("\n" + "=" * 70)
    print("Uploading ZIP via SFTP")
    print("=" * 70)
    
    if not SSH_KEY_PATH.exists():
        print(f"✗ SSH key not found: {SSH_KEY_PATH}")
        return False
    
    try:
        # Create SSH client
        ssh = paramiko.SSHClient()
        ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
        
        # Load private key
        private_key = paramiko.Ed25519Key.from_private_key_file(str(SSH_KEY_PATH))
        
        # Connect
        print(f"\n[1] Connecting to {SSH_USER}@{SSH_HOST}:{SSH_PORT}...")
        ssh.connect(
            hostname=SSH_HOST,
            port=SSH_PORT,
            username=SSH_USER,
            pkey=private_key,
            timeout=30
        )
        print("✓ Connected")
        
        # Open SFTP session
        sftp = ssh.open_sftp()
        
        # Upload zip file
        zip_size = zip_path.stat().st_size / (1024 * 1024)
        remote_zip = f"{REMOTE_PATH}/{ZIP_NAME}"
        
        print(f"\n[2] Uploading {ZIP_NAME} ({zip_size:.2f} MB)...")
        print("  This may take a few minutes...")
        
        sftp.put(str(zip_path), remote_zip)
        
        print(f"✓ Upload complete: {remote_zip}")
        
        sftp.close()
        ssh.close()
        
        return True
        
    except Exception as e:
        print(f"✗ Upload failed: {e}")
        return False

def extract_on_server():
    """Extract zip file on server"""
    print("\n" + "=" * 70)
    print("Extracting ZIP on Server")
    print("=" * 70)
    
    try:
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
        
        print(f"\n[1] Extracting {ZIP_NAME} on server...")
        
        # Extract zip file
        extract_cmd = f"cd {REMOTE_PATH} && unzip -o {ZIP_NAME} && rm {ZIP_NAME}"
        stdin, stdout, stderr = ssh.exec_command(extract_cmd)
        
        exit_status = stdout.channel.recv_exit_status()
        
        if exit_status == 0:
            print("✓ Extraction complete")
            print("✓ ZIP file removed from server")
        else:
            error = stderr.read().decode()
            print(f"⚠ Extraction warning: {error}")
        
        ssh.close()
        return True
        
    except Exception as e:
        print(f"✗ Extraction failed: {e}")
        return False

def main():
    print("=" * 70)
    print("ZIP & SFTP Upload to Staging")
    print("=" * 70)
    
    # Step 1: Create zip
    zip_path = create_zip()
    
    # Step 2: Upload zip
    if not upload_zip(zip_path):
        return
    
    # Step 3: Extract on server (automatic)
    print("\n[3] Extracting ZIP on server...")
    if extract_on_server():
        print("✓ All files extracted to staging directory")
    else:
        print(f"\n⚠ ZIP uploaded but extraction failed")
        print(f"  Extract manually: ssh -p 65002 -i id_rsa_skilltricks {SSH_USER}@{SSH_HOST} 'cd {REMOTE_PATH} && unzip -o {ZIP_NAME} && rm {ZIP_NAME}'")
    
    # Clean up local zip
    print(f"\n[4] Cleaning up...")
    try:
        zip_path.unlink()
        print(f"✓ Deleted local {ZIP_NAME}")
    except:
        print(f"⚠ Could not delete {ZIP_NAME}")
    
    print("\n" + "=" * 70)
    print("Deployment Complete!")
    print("=" * 70)

if __name__ == '__main__':
    try:
        import paramiko
    except ImportError:
        print("✗ paramiko library not installed")
        print("  Install it with: pip3 install paramiko --break-system-packages")
        exit(1)
    
    main()

