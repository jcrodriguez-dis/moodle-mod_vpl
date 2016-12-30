/*
Multy line comment
 */
#include <stdio.h>
// Line comment

int example( int a, int b);

struct Name {
	int v;
	char * s;
} a[3];
union  Name {
	int v;
	char * s;
} * p;
using namespace B;

namespace Nombre;
class Class: public A{
private:
   int field;
public:
   Class():fiels(3) {
   }
private:
   inline int f(int n){
      return 3;
   }
protected:
   Class& operator=(Class &o){
      return this;
   }
   char change(int v){
      return 'A'+v;
   }
};
int main(){
	int v[]={1,2,3,4};
	for ( int i = 0; i < sizeof v ; i++){
		int j = 0;
		while ( j <= 4 ){
			int k = -1;
			do {
				k++;
			}
			while ( k < 4);
			switch ( j ) {
			case 1:
				break;
			case 2:
				break;
			default:
			}
		}
		continue;
	}
	if( a < 3 ) {
		c = 3;
	} else f (d) {
		b = 8;
	} else {
		b = 8;
		d = 'a';
	}
	printf("texto");
	printf("texto'\"\"\\");
}

// End

