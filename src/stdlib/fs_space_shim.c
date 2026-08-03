#include <stdint.h>
#include <sys/statvfs.h>

int zphp_disk_space(const char *path, uint64_t *free_bytes, uint64_t *total_bytes) {
    struct statvfs st;
    if (statvfs(path, &st) != 0) return -1;
    *free_bytes = (uint64_t)st.f_bavail * (uint64_t)st.f_frsize;
    *total_bytes = (uint64_t)st.f_blocks * (uint64_t)st.f_frsize;
    return 0;
}
