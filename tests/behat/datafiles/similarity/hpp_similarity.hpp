class VectorOfIntegers
{
public:
    VectorOfIntegers(int length);
    void set(int index, int value);
    int get(int index);
    int length();
private:
    int *vector;
    int length;
}
