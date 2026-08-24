import { execSync } from 'child_process';

const SECRET_PATTERNS = [
    { name: 'Stripe Secret Key', regex: /sk_(live|test)_[0-9a-zA-Z]{24,}/ },
    { name: 'Stripe Restricted Key', regex: /rk_(live|test)_[0-9a-zA-Z]{24,}/ },
    { name: 'Stripe Publishable Key', regex: /pk_(live|test)_[0-9a-zA-Z]{24,}/ },
    { name: 'AWS Access Key ID', regex: /(A3T[A-Z0-9]|AKIA|AGPA|AIDA|AROA|AIPA|ANPA|ANVA|ASIA)[A-Z0-9]{16}/ },
    { name: 'Google API Key', regex: /AIza[0-9A-Za-z-_]{35}/ },
    { name: 'Slack Webhook', regex: /https:\/\/hooks\.slack\.com\/services\/[A-Za-z0-9_]+\/[A-Za-z0-9_]+\/[A-Za-z0-9_]+/ },
    { name: 'Private Key block', regex: /-----BEGIN (RSA|EC|DSA|OPENSSH) PRIVATE KEY-----/ }
];

try {
    // Get list of staged files
    const stagedFiles = execSync('git diff --cached --name-only --diff-filter=d')
        .toString()
        .trim()
        .split('\n')
        .filter(Boolean);

    if (stagedFiles.length === 0) {
        process.exit(0);
    }

    let foundSecrets = false;

    for (const file of stagedFiles) {
        // Skip check for lockfiles or vendor packages
        if (file.endsWith('-lock.json') || file.endsWith('.lock') || file.includes('node_modules/') || file.includes('vendor/')) {
            continue;
        }

        // Get diff of staged additions for this file
        const diff = execSync(`git diff --cached -- "${file}"`).toString();
        const lines = diff.split('\n');
        
        for (let i = 0; i < lines.length; i++) {
            const line = lines[i];
            
            // Only check staged additions (lines starting with + but not +++)
            if (line.startsWith('+') && !line.startsWith('+++')) {
                const content = line.substring(1);
                
                for (const pattern of SECRET_PATTERNS) {
                    if (pattern.regex.test(content)) {
                        console.error(`\x1b[31m[SECURITY ALERT] Found potential ${pattern.name} in file: ${file}\x1b[0m`);
                        console.error(`Line: ${line.trim()}`);
                        console.error(`\x1b[33mCommit aborted! Please remove the credential/secret and try again.\x1b[0m\n`);
                        foundSecrets = true;
                    }
                }
            }
        }
    }

    if (foundSecrets) {
        process.exit(1);
    }
    
    process.exit(0);
} catch (error) {
    console.error('Error during secret scan:', error.message);
    process.exit(0); // Pass in case of command failure to avoid blocking developer
}
