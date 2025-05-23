#!/usr/bin/env bash
SOURCE_DIRECTORY=$(pwd)

# Verify that there (potentially) some access to the destination repository
# and set up git (with GIT_CMD variable) and GIT_CMD_REPOSITORY
if [ -n "${SSH_DEPLOY_KEY:=}" ]; then
	echo "[+] Using SSH_DEPLOY_KEY"

	mkdir --parents "$HOME/.ssh"
	DEPLOY_KEY_FILE="$HOME/.ssh/deploy_key"
	echo "${SSH_DEPLOY_KEY}" >"$DEPLOY_KEY_FILE"
	chmod 600 "$DEPLOY_KEY_FILE"

	SSH_KNOWN_HOSTS_FILE="$HOME/.ssh/known_hosts"
	ssh-keyscan -H "github.com" >"$SSH_KNOWN_HOSTS_FILE"

	git config --global user.name "$GIT_USER_NAME"
	git config --global user.email "$GIT_USER_EMAIL"

	export GIT_SSH_COMMAND="ssh -i "$DEPLOY_KEY_FILE" -o UserKnownHostsFile=$SSH_KNOWN_HOSTS_FILE"

	GIT_CMD_REPOSITORY="git@github.com:$DESTINATION_REPOSITORY_USERNAME/$DESTINATION_REPOSITORY_NAME.git"
	git submodule update --init --recursive
	echo "${SSH_DEPLOY_KEY}" >"$DEPLOY_KEY_FILE"
else
	echo "::error::SSH_DEPLOY_KEY is empty."
	exit 1
fi

### Preparing release folder
rm -rf .git .github .gitmodules .fury *.sh Dockerfile* bin/setup-release.sh bin/sync-sdk.sh bin/create-release-zip.sh

cd packages/sdk/
composer dump-autoload -o
find . -maxdepth 1 -not \( -name "." -o -name ".." -o -name "src" -o -name "composer.json" -o -name "vendor" \) -exec rm -r {} +

CLONE_DIR=$(mktemp -d)

echo "[+] Git version"
git --version

echo "[+] Cloning destination git repository $DESTINATION_REPOSITORY_NAME"
{
	git clone --single-branch --depth 1 "$GIT_CMD_REPOSITORY" "$CLONE_DIR"
} || {
	echo "::error::Could not clone the destination repository. Command:"
	echo "::error::git clone --single-branch $GIT_CMD_REPOSITORY $CLONE_DIR"
	echo "::error::(Note that if they exist USER_NAME and API_TOKEN is redacted by GitHub)"
	echo "::error::Please verify that the target repository exist AND that it contains the destination branch name, and is accesible by the SSH_DEPLOY_KEY"
	exit 1
}
ls -la "$CLONE_DIR"

echo "[+] List contents of $SOURCE_DIRECTORY"
ls "$SOURCE_DIRECTORY"

echo "[+] Copying contents of source repository folder $SOURCE_DIRECTORY to folder $CLONE_DIR in git repo $DESTINATION_REPOSITORY_NAME"
cp -Ra "$SOURCE_DIRECTORY"/. "$CLONE_DIR/"
cd "$CLONE_DIR"

TARGET_BRANCH="release/$PACKAGE_VERSION"
git checkout -b $TARGET_BRANCH

echo "[+] Files that will be pushed"
ls -la

echo "[+] Set directory is safe ($CLONE_DIR)"
git config --global --add safe.directory "$CLONE_DIR"

echo "[+] git add"
git add .

echo "[+] git status:"
git status

echo "[+] git commit:"
git commit -m "Release v$PACKAGE_VERSION"

echo "[+] Pushing git commit"
# --set-upstream: sets the branch when pushing to a branch that does not exist
git push "${GIT_CMD_REPOSITORY}" --set-upstream "$TARGET_BRANCH"
