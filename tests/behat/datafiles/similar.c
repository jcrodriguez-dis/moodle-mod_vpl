#include <stdio.h>
int main(){
	printf("Hello from C language!\n");
	for ( int i = 0; i < 10; i++) {
		if ( i % 7 == 0) {
			printf("%d\n",  i + 3);
		}
	}
	return 0;
}
