#include "h_similarity.h"
#include <stdio.h>

#define abs(num) num < 0 ? num * -1 : num

#define NUM_LEN 10

int cumsum(int *v1, int length)
{
    int result = 0;

    if (v1 != NULL)
    {
        for (int i = 0; i < length; i++)
            result = result + *(v1 + i);
    }

    return result;
}

int main(int nargc, char *argv[])
{
    printf("Hello, world!");

    for (int i = 0; i < 10; i++)
    {
		if (i % 7 == 0)
        {
			printf("%d\n",  i + 3);
		}
	}

    return 0;
}