#!/usr/bin/env bash
#
# test-htaccess.sh — regression matrix for public/.htaccess (INC-001).
#
# This file caused a production outage twice in three days, so it gets a test.
#
#   1st failure: the ciphra migration endpoints were canonicalised to www.
#      A cross-origin CORS fetch will not follow a redirect that carries no
#      Access-Control-Allow-Origin, so the browser aborted before reaching PHP
#      and a migrating user lost his transfer with no server-side trace.
#
#   2nd failure: the exemption was written against %{REQUEST_URI}. The Symfony
#      front controller rewrites everything to index.php, which makes Apache
#      re-run the ruleset; on that pass REQUEST_URI is already "/index.php",
#      the exemption stopped matching, and the redirect fired anyway — now
#      dropping the path. Fixed by using %{THE_REQUEST}, which is the verbatim
#      request line and is never rewritten.
#
# Until 2026-08-10 this file could not be tested outside production: the dev
# container ran AllowOverride None, so Apache ignored it entirely. It now runs
# AllowOverride All, and the canonicalisation rule skips loopback hosts so the
# local stack still works.
#
# Usage (with the dev stack up):
#   docker compose up -d --build app
#   scripts/test-htaccess.sh
#
set -uo pipefail

BASE="${EPILEPC_BASE:-http://localhost:8081}"
FAIL=0

check() {
	local host="$1" path="$2" expect="$3" desc="$4"
	local out status location
	out="$(curl -s -o /dev/null -D - --max-time 10 -H "Host: $host" "${BASE}${path}" 2>/dev/null | tr -d '\r')"
	status="$(printf '%s' "$out" | awk 'NR==1{print $2}')"
	location="$(printf '%s' "$out" | grep -i '^location:' | head -1 | cut -d' ' -f2-)"

	local ok=1
	case "$expect" in
		no-redirect) [ "${status:0:1}" = "3" ] && ok=0 ;;
		redirect)    [ "${status:0:1}" = "3" ] || ok=0 ;;
	esac

	if [ "$ok" -eq 1 ]; then
		printf '  \033[32m✓\033[0m %-52s %s %s\n' "$desc" "$status" "$location"
	else
		printf '  \033[31m✗\033[0m %-52s %s %s\n' "$desc" "$status" "$location"
		FAIL=1
	fi
}

printf '\033[1mpublic/.htaccess regression matrix\033[0m  (%s)\n\n' "$BASE"

printf '\033[1mMigration endpoints must NEVER be redirected\033[0m\n'
check epilepc.ch /api/ciphra-export/probe      no-redirect "apex → /api/ciphra-export/"
check epilepc.ch /api/migration-complete/probe no-redirect "apex → /api/migration-complete/"
check direct.epilepc.ch /api/ciphra-export/probe no-redirect "direct → /api/ciphra-export/"
echo

printf '\033[1mEverything else still canonicalises to www, path intact\033[0m\n'
check epilepc.ch /de/login redirect "apex → /de/login"
check epilepc.ch /         redirect "apex → /"
echo

printf '\033[1mLocal dev host is never canonicalised\033[0m\n'
check localhost:8081 /de/login no-redirect "localhost → /de/login"
echo

printf '\033[1mwww passes through untouched\033[0m\n'
check www.epilepc.ch /api/ciphra-export/probe no-redirect "www → /api/ciphra-export/"
check www.epilepc.ch /de/login                no-redirect "www → /de/login"
echo

# The path-loss signature of the 2nd failure: a redirect to /index.php means
# the ruleset ran a second time and the exemption was evaluated too late.
printf '\033[1mNo redirect may point at /index.php\033[0m\n'
loc="$(curl -s -o /dev/null -D - --max-time 10 -H "Host: epilepc.ch" \
	"${BASE}/api/ciphra-export/probe" 2>/dev/null | tr -d '\r' | grep -i '^location:' | head -1)"
if printf '%s' "$loc" | grep -q 'index\.php'; then
	printf '  \033[31m✗\033[0m exemption evaluated after the front-controller rewrite\n'
	printf '    use %%{THE_REQUEST}, not %%{REQUEST_URI}\n'
	FAIL=1
else
	printf '  \033[32m✓\033[0m exemption survives the front-controller rewrite\n'
fi
echo

if [ "$FAIL" -eq 0 ]; then
	printf '\033[32m\033[1mPASS\033[0m\n'; exit 0
fi
printf '\033[31m\033[1mFAIL\033[0m\n'; exit 1
