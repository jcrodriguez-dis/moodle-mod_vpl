#include <stdio.h>

int main(int nargc, char *argv[])
{
    if (nargc > 1)
    {
        for (int i = 0; i < nargc; i++)
        {
            printf("%d\n", i);

            // This is just a comment
            char *str = "Hello world";
        }
    }

    return 0;
}